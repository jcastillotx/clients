<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;

class DocumentAccessService
{
    public function canView(User $user, Document $document): bool
    {
        if (!$user->isClient()) {
            return true;
        }

        if ((int) $user->client_id !== (int) $document->client_id) {
            return false;
        }

        $perm = $this->userPermission($user, $document);
        return $perm ? (bool) $perm->can_view : true; // default allow within client
    }

    public function canDownload(User $user, Document $document): bool
    {
        if (!$user->isClient()) {
            return true;
        }

        if ((int) $user->client_id !== (int) $document->client_id) {
            return false;
        }

        $perm = $this->userPermission($user, $document);
        return $perm ? (bool) $perm->can_download : true;
    }

    public function canUploadNewVersion(User $user, Document $document): bool
    {
        if (!$user->isClient()) {
            return true;
        }

        if ((int) $user->client_id !== (int) $document->client_id) {
            return false;
        }

        $perm = $this->userPermission($user, $document);
        return $perm ? (bool) $perm->can_upload_version : false; // default deny for client
    }

    public function canShare(User $user, Document $document): bool
    {
        // For now: share requires download permission
        return $this->canDownload($user, $document);
    }

    private function userPermission(User $user, Document $document): ?DocumentPermission
    {
        return DocumentPermission::query()
            ->where('document_id', $document->id)
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->first();
    }
}

