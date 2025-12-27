<?php

namespace App\Contracts;

/**
 * Provider-agnostic contract for cloud storage integrations.
 *
 * Implementations should throw a meaningful exception when a method
 * is not supported by a provider (e.g. share links).
 */
interface StorageProviderInterface
{
    /**
     * Authenticate and establish connection.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function connect(array $credentials): bool;

    /**
     * Revoke access / disconnect integration.
     */
    public function disconnect(): bool;

    /**
     * List files/folders in a directory.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFiles(string $path): array;

    /**
     * Upload a file to provider.
     *
     * @param  mixed  $file  e.g. UploadedFile|string path|stream
     */
    public function uploadFile(mixed $file, string $path): array;

    /**
     * Download a file from provider.
     *
     * @return array{stream:mixed, file_name?:string, mime_type?:string}|mixed
     */
    public function downloadFile(string $fileId): mixed;

    /**
     * Delete a file from provider.
     */
    public function deleteFile(string $fileId): bool;

    /**
     * Create a folder in provider.
     */
    public function createFolder(string $name, string $parentPath): array;

    /**
     * Get a shareable URL for a file.
     */
    public function getFileUrl(string $fileId): ?string;

    /**
     * Get used/total storage information (bytes).
     *
     * @return array{used:int, total:int|null}
     */
    public function getStorageUsage(): array;
}
