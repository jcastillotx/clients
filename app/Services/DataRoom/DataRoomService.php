<?php

namespace App\Services\DataRoom;

use App\Models\DataRoom;
use App\Models\DataRoomAccess;
use App\Models\DataRoomActivityLog;
use App\Models\DataRoomFile;
use App\Models\DataRoomFolder;
use App\Models\DataRoomInvitation;
use App\Models\User;
use App\Services\Security\EncryptionService;
use App\Services\Security\S3EncryptedStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data Room Service
 *
 * Handles all business logic for secure data rooms including:
 * - Room creation and management
 * - File encryption/decryption
 * - Access control management
 * - Activity logging
 */
class DataRoomService
{
    public function __construct(
        protected EncryptionService $encryptionService,
        protected S3EncryptedStorageService $storageService
    ) {}

    /**
     * Create a new data room.
     */
    public function createRoom(
        int $clientId,
        User $creator,
        string $name,
        ?string $description = null,
        array $settings = []
    ): DataRoom {
        return DB::transaction(function () use ($clientId, $creator, $name, $description, $settings) {
            $room = DataRoom::create([
                'client_id' => $clientId,
                'created_by' => $creator->id,
                'name' => $name,
                'description' => $description,
                'require_2fa' => $settings['require_2fa'] ?? true,
                'require_watermark' => $settings['require_watermark'] ?? false,
                'allow_download' => $settings['allow_download'] ?? true,
                'allow_print' => $settings['allow_print'] ?? false,
                'session_timeout_minutes' => $settings['session_timeout_minutes'] ?? 30,
                'storage_provider' => $settings['storage_provider'] ?? 's3',
                'storage_bucket' => $settings['storage_bucket'] ?? config('filesystems.disks.s3.bucket'),
            ]);

            // Grant admin access to creator
            DataRoomAccess::create([
                'data_room_id' => $room->id,
                'user_id' => $creator->id,
                'granted_by' => $creator->id,
                'permission_level' => 'admin',
                'can_view' => true,
                'can_download' => true,
                'can_upload' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_share' => true,
                'can_manage_access' => true,
                'is_active' => true,
            ]);

            // Log creation
            DataRoomActivityLog::log($room, 'room_created', 'room', $room->id, [
                'name' => $name,
                'settings' => $settings,
            ]);

            return $room;
        });
    }

    /**
     * Upload a file to a data room.
     */
    public function uploadFile(
        DataRoom $room,
        User $user,
        UploadedFile $file,
        ?int $folderId = null,
        ?string $customName = null
    ): DataRoomFile {
        // Verify access
        $this->verifyAccess($room, $user, 'upload');

        // Verify folder access if specified
        if ($folderId) {
            $folder = DataRoomFolder::findOrFail($folderId);
            if ($folder->data_room_id !== $room->id) {
                throw new RuntimeException('Folder does not belong to this data room');
            }
        }

        return DB::transaction(function () use ($room, $user, $file, $folderId, $customName) {
            $fileName = $customName ?? $file->getClientOriginalName();
            $storagePath = $room->storage_prefix.'/'.uniqid().'_'.$file->hashName();

            // Upload with encryption
            $result = $this->storageService->uploadFile(
                $file,
                $room->storage_prefix,
                $room->decrypted_key
            );

            // Create file record
            $dataRoomFile = DataRoomFile::create([
                'data_room_id' => $room->id,
                'uploaded_by' => $user->id,
                'folder_id' => $folderId,
                'name' => $fileName,
                'original_filename' => $file->getClientOriginalName(),
                'storage_path' => $result['path'],
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'encryption_iv' => $result['encrypted']['iv'],
                'encryption_tag' => $result['encrypted']['tag'],
                'checksum' => $result['encrypted']['checksum'],
                'encrypted_checksum' => $this->encryptionService->hash(base64_decode($result['encrypted']['checksum'])),
                'version' => 1,
                'status' => 'active',
            ]);

            // Log upload
            DataRoomActivityLog::log($room, 'file_uploaded', 'file', $dataRoomFile->id, [
                'filename' => $fileName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return $dataRoomFile;
        });
    }

    /**
     * Download a file from a data room.
     */
    public function downloadFile(DataRoom $room, User $user, DataRoomFile $file): string
    {
        // Verify access
        $this->verifyAccess($room, $user, 'download');

        // Verify file belongs to room
        if ($file->data_room_id !== $room->id) {
            throw new RuntimeException('File does not belong to this data room');
        }

        // Check room allows downloads
        if (! $room->allow_download) {
            throw new RuntimeException('Downloads are disabled for this data room');
        }

        // Download and decrypt
        $contents = $this->storageService->downloadDecrypted(
            $file->storage_path,
            $room->decrypted_key
        );

        // Verify integrity
        if (! $this->encryptionService->verifyChecksum($contents, $file->checksum)) {
            DataRoomActivityLog::log($room, 'file_downloaded', 'file', $file->id, [], 'failed', 'Integrity check failed');
            throw new RuntimeException('File integrity verification failed');
        }

        // Log download
        DataRoomActivityLog::log($room, 'file_downloaded', 'file', $file->id, [
            'filename' => $file->name,
        ]);

        // Record access
        $access = $room->getUserAccess($user);
        if ($access) {
            $access->recordAccess();
        }
        $room->recordAccess();

        return $contents;
    }

    /**
     * Delete a file from a data room.
     */
    public function deleteFile(DataRoom $room, User $user, DataRoomFile $file): bool
    {
        // Verify access
        $this->verifyAccess($room, $user, 'delete');

        // Verify file belongs to room
        if ($file->data_room_id !== $room->id) {
            throw new RuntimeException('File does not belong to this data room');
        }

        // Check if file is locked
        if ($file->is_locked && $file->locked_by !== $user->id) {
            throw new RuntimeException('File is locked by another user');
        }

        return DB::transaction(function () use ($room, $file) {
            // Delete from storage
            $this->storageService->delete($file->storage_path);

            // Soft delete the record
            $file->delete();

            // Log deletion
            DataRoomActivityLog::log($room, 'file_deleted', 'file', $file->id, [
                'filename' => $file->name,
            ]);

            return true;
        });
    }

    /**
     * Create a folder in a data room.
     */
    public function createFolder(
        DataRoom $room,
        User $user,
        string $name,
        ?int $parentId = null
    ): DataRoomFolder {
        // Verify access
        $this->verifyAccess($room, $user, 'upload');

        // Verify parent folder if specified
        if ($parentId) {
            $parent = DataRoomFolder::findOrFail($parentId);
            if ($parent->data_room_id !== $room->id) {
                throw new RuntimeException('Parent folder does not belong to this data room');
            }
        }

        $folder = DataRoomFolder::create([
            'data_room_id' => $room->id,
            'parent_id' => $parentId,
            'created_by' => $user->id,
            'name' => $name,
        ]);

        // Log creation
        DataRoomActivityLog::log($room, 'folder_created', 'folder', $folder->id, [
            'name' => $name,
            'parent_id' => $parentId,
        ]);

        return $folder;
    }

    /**
     * Grant access to a user.
     */
    public function grantAccess(
        DataRoom $room,
        User $granter,
        User $user,
        string $permissionLevel,
        array $options = []
    ): DataRoomAccess {
        // Verify granter has manage_access permission
        $this->verifyAccess($room, $granter, 'manage_access');

        // Check if user already has access
        $existingAccess = DataRoomAccess::where('data_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAccess) {
            // Update existing access
            $existingAccess->setPermissionLevel($permissionLevel);
            $existingAccess->update([
                'expires_at' => $options['expires_at'] ?? null,
                'allowed_ips' => $options['allowed_ips'] ?? null,
                'allowed_folders' => $options['allowed_folders'] ?? null,
                'require_2fa' => $options['require_2fa'] ?? true,
                'is_active' => true,
            ]);

            DataRoomActivityLog::log($room, 'access_modified', 'access', $existingAccess->id, [
                'user_id' => $user->id,
                'permission_level' => $permissionLevel,
            ]);

            return $existingAccess;
        }

        // Create new access
        $access = DataRoomAccess::create([
            'data_room_id' => $room->id,
            'user_id' => $user->id,
            'granted_by' => $granter->id,
            'permission_level' => $permissionLevel,
            'expires_at' => $options['expires_at'] ?? null,
            'allowed_ips' => $options['allowed_ips'] ?? null,
            'allowed_folders' => $options['allowed_folders'] ?? null,
            'require_2fa' => $options['require_2fa'] ?? true,
            'is_active' => true,
        ]);

        DataRoomActivityLog::log($room, 'access_granted', 'access', $access->id, [
            'user_id' => $user->id,
            'permission_level' => $permissionLevel,
        ]);

        return $access;
    }

    /**
     * Revoke access from a user.
     */
    public function revokeAccess(DataRoom $room, User $revoker, User $user): bool
    {
        // Verify revoker has manage_access permission
        $this->verifyAccess($room, $revoker, 'manage_access');

        // Cannot revoke own access if you're the only admin
        if ($user->id === $revoker->id) {
            $adminCount = $room->accessGrants()
                ->where('permission_level', 'admin')
                ->where('is_active', true)
                ->count();

            if ($adminCount <= 1) {
                throw new RuntimeException('Cannot revoke access: You are the only admin');
            }
        }

        $access = DataRoomAccess::where('data_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $access) {
            return false;
        }

        $access->revoke();

        DataRoomActivityLog::log($room, 'access_revoked', 'access', $access->id, [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Send an invitation to a data room.
     */
    public function sendInvitation(
        DataRoom $room,
        User $inviter,
        string $email,
        string $permissionLevel,
        array $permissions = []
    ): DataRoomInvitation {
        // Verify inviter has share permission
        $this->verifyAccess($room, $inviter, 'share');

        // Check for existing pending invitation
        $existing = DataRoomInvitation::where('data_room_id', $room->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            // Resend existing invitation
            $existing->resend();

            return $existing;
        }

        $invitation = DataRoomInvitation::create([
            'data_room_id' => $room->id,
            'invited_by' => $inviter->id,
            'email' => $email,
            'permission_level' => $permissionLevel,
            'permissions' => $permissions,
        ]);

        DataRoomActivityLog::log($room, 'invitation_sent', 'invitation', $invitation->id, [
            'email' => $email,
            'permission_level' => $permissionLevel,
        ]);

        return $invitation;
    }

    /**
     * Get files in a data room (with folder filtering).
     */
    public function getFiles(DataRoom $room, User $user, ?int $folderId = null): Collection
    {
        // Verify access
        $this->verifyAccess($room, $user, 'view');

        // Check folder access
        $access = $room->getUserAccess($user);
        if ($access && ! $access->isFolderAllowed($folderId)) {
            throw new RuntimeException('Access to this folder is not allowed');
        }

        // Log access
        DataRoomActivityLog::log($room, 'room_accessed', 'room', $room->id, [
            'folder_id' => $folderId,
        ]);

        $room->recordAccess();

        return DataRoomFile::where('data_room_id', $room->id)
            ->where('status', 'active')
            ->inFolder($folderId)
            ->with('uploader')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get folders in a data room.
     */
    public function getFolders(DataRoom $room, User $user, ?int $parentId = null): Collection
    {
        // Verify access
        $this->verifyAccess($room, $user, 'view');

        return DataRoomFolder::where('data_room_id', $room->id)
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Verify user has required access.
     */
    protected function verifyAccess(DataRoom $room, User $user, string $permission): void
    {
        // Check room status
        if ($room->isLocked()) {
            throw new RuntimeException('Data room is locked');
        }

        if ($room->isArchived()) {
            throw new RuntimeException('Data room is archived');
        }

        // Get access record
        $access = $room->getUserAccess($user);

        if (! $access) {
            DataRoomActivityLog::log($room, 'room_accessed', 'room', $room->id, [], 'blocked', 'No access');
            throw new RuntimeException('You do not have access to this data room');
        }

        if (! $access->isValid()) {
            DataRoomActivityLog::log($room, 'room_accessed', 'room', $room->id, [], 'blocked', 'Access expired');
            throw new RuntimeException('Your access has expired');
        }

        // Check IP if restricted
        if (! $access->isIpAllowed(request()->ip())) {
            DataRoomActivityLog::log($room, 'ip_blocked', 'room', $room->id, [
                'ip' => request()->ip(),
            ], 'blocked', 'IP not in allowlist');
            throw new RuntimeException('Access from your IP address is not allowed');
        }

        // Check 2FA requirement
        if ($access->require_2fa && ! session('2fa_verified', false)) {
            DataRoomActivityLog::log($room, 'room_accessed', 'room', $room->id, [], 'blocked', '2FA required');
            throw new RuntimeException('Two-factor authentication is required');
        }

        // Check specific permission
        if (! $access->hasPermission($permission)) {
            DataRoomActivityLog::log($room, 'room_accessed', 'room', $room->id, [
                'required_permission' => $permission,
            ], 'blocked', 'Insufficient permissions');
            throw new RuntimeException("You do not have permission to {$permission}");
        }
    }

    /**
     * Generate a pre-signed URL for file viewing (without download).
     */
    public function generateViewUrl(DataRoom $room, User $user, DataRoomFile $file, int $expiresInMinutes = 15): string
    {
        $this->verifyAccess($room, $user, 'view');

        if ($file->data_room_id !== $room->id) {
            throw new RuntimeException('File does not belong to this data room');
        }

        DataRoomActivityLog::log($room, 'file_viewed', 'file', $file->id, [
            'filename' => $file->name,
        ]);

        return $this->storageService->generatePresignedUrl($file->storage_path, $expiresInMinutes);
    }
}
