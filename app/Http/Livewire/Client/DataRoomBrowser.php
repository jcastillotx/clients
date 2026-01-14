<?php

namespace App\Http\Livewire\Client;

use App\Models\DataRoom;
use App\Models\DataRoomFile;
use App\Models\DataRoomFolder;
use App\Services\DataRoom\DataRoomService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Data Room Browser Component
 *
 * Provides secure browsing of encrypted data rooms with:
 * - AES-256 encrypted file upload/download
 * - Role-based access control
 * - SOC2 compliant activity logging
 */
class DataRoomBrowser extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $roomId = null;

    public ?int $folderId = null;

    public string $searchQuery = '';

    public array $selectedFiles = [];

    public bool $showUploadModal = false;

    public bool $showNewFolderModal = false;

    public bool $showAccessModal = false;

    public string $newFolderName = '';

    public $uploadFiles = [];

    protected DataRoomService $dataRoomService;

    protected $listeners = ['refreshFiles' => '$refresh'];

    public function boot(DataRoomService $dataRoomService): void
    {
        $this->dataRoomService = $dataRoomService;
    }

    public function mount(?int $roomId = null): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        if ($roomId) {
            $this->roomId = $roomId;
            $this->verifyRoomAccess();
        } else {
            // Get first accessible room
            $room = DataRoom::whereHas('accessGrants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })->where('client_id', $user->client_id)->first();

            $this->roomId = $room?->id;
        }
    }

    public function selectRoom(int $id): void
    {
        $this->roomId = $id;
        $this->folderId = null;
        $this->selectedFiles = [];
        $this->verifyRoomAccess();
    }

    public function openFolder(int $id): void
    {
        $folder = DataRoomFolder::findOrFail($id);
        abort_unless($folder->data_room_id === $this->roomId, 403);
        $this->folderId = $id;
        $this->selectedFiles = [];
    }

    public function navigateUp(): void
    {
        if ($this->folderId) {
            $folder = DataRoomFolder::find($this->folderId);
            $this->folderId = $folder?->parent_id;
        }
    }

    public function navigateToBreadcrumb(?int $folderId): void
    {
        $this->folderId = $folderId;
        $this->selectedFiles = [];
    }

    public function toggleFileSelection(int $fileId): void
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = array_diff($this->selectedFiles, [$fileId]);
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }

    public function selectAllFiles(): void
    {
        $files = $this->getFiles();
        $this->selectedFiles = $files->pluck('id')->toArray();
    }

    public function clearSelection(): void
    {
        $this->selectedFiles = [];
    }

    public function createFolder(): void
    {
        $this->validate([
            'newFolderName' => 'required|string|max:255',
        ]);

        $room = $this->getRoom();
        $user = Auth::user();

        $this->dataRoomService->createFolder($room, $user, $this->newFolderName, $this->folderId);

        $this->newFolderName = '';
        $this->showNewFolderModal = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Folder created successfully']);
    }

    public function uploadFile(): void
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:'.((int) config('security.data_rooms.max_file_size_mb', 500) * 1024),
        ]);

        $room = $this->getRoom();
        $user = Auth::user();

        foreach ($this->uploadFiles as $file) {
            $this->dataRoomService->uploadFile($room, $user, $file, $this->folderId);
        }

        $this->uploadFiles = [];
        $this->showUploadModal = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Files uploaded successfully']);
    }

    public function downloadFile(int $fileId): void
    {
        $room = $this->getRoom();
        $user = Auth::user();
        $file = DataRoomFile::findOrFail($fileId);

        abort_unless($file->data_room_id === $this->roomId, 403);

        try {
            $contents = $this->dataRoomService->downloadFile($room, $user, $file);

            // Return file as download
            $this->dispatch('downloadFile', [
                'filename' => $file->original_filename,
                'content' => base64_encode($contents),
                'mimeType' => $file->mime_type,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteFile(int $fileId): void
    {
        $room = $this->getRoom();
        $user = Auth::user();
        $file = DataRoomFile::findOrFail($fileId);

        abort_unless($file->data_room_id === $this->roomId, 403);

        try {
            $this->dataRoomService->deleteFile($room, $user, $file);
            $this->selectedFiles = array_diff($this->selectedFiles, [$fileId]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteSelectedFiles(): void
    {
        foreach ($this->selectedFiles as $fileId) {
            $this->deleteFile($fileId);
        }
        $this->selectedFiles = [];
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        // Get accessible rooms
        $rooms = DataRoom::whereHas('accessGrants', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        })
            ->where('client_id', $user->client_id)
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();

        $room = null;
        $files = collect();
        $folders = collect();
        $breadcrumbs = [];
        $access = null;

        if ($this->roomId) {
            $room = $this->getRoom();
            $access = $room?->getUserAccess($user);

            if ($room && $access) {
                $files = $this->getFiles();
                $folders = $this->getFolders();
                $breadcrumbs = $this->getBreadcrumbs();
            }
        }

        return view('livewire.client.data-room-browser', compact(
            'rooms',
            'room',
            'files',
            'folders',
            'breadcrumbs',
            'access'
        ))->layout('layouts.app');
    }

    protected function getRoom(): ?DataRoom
    {
        if (! $this->roomId) {
            return null;
        }

        return DataRoom::find($this->roomId);
    }

    protected function getFiles()
    {
        $query = DataRoomFile::where('data_room_id', $this->roomId)
            ->where('status', 'active')
            ->inFolder($this->folderId)
            ->with('uploader');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('original_filename', 'like', '%'.$this->searchQuery.'%');
            });
        }

        return $query->orderBy('name')->get();
    }

    protected function getFolders()
    {
        return DataRoomFolder::where('data_room_id', $this->roomId)
            ->where('parent_id', $this->folderId)
            ->orderBy('name')
            ->get();
    }

    protected function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->folderId) {
            $folder = DataRoomFolder::find($this->folderId);
            if ($folder) {
                $breadcrumbs = $folder->getBreadcrumbs();
            }
        }

        return $breadcrumbs;
    }

    protected function verifyRoomAccess(): void
    {
        $user = Auth::user();
        $room = DataRoom::find($this->roomId);

        abort_unless($room, 404, 'Data room not found');
        abort_unless($room->client_id === $user->client_id, 403, 'Access denied');
        abort_unless($room->userHasAccess($user, 'view'), 403, 'Access denied');
    }
}
