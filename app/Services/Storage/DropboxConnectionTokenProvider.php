<?php

namespace App\Services\Storage;

use App\Models\StorageConnection;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;
use Spatie\Dropbox\RefreshableTokenProvider;

class DropboxConnectionTokenProvider implements RefreshableTokenProvider
{
    public function __construct(
        protected StorageConnection $connection,
        protected string $appKey,
        protected string $appSecret,
        protected string $redirectUri,
    ) {}

    public function getToken(): string
    {
        $creds = (array) ($this->connection->credentials ?? []);
        return (string) ($creds['access_token'] ?? '');
    }

    public function refresh(ClientException $exception): bool
    {
        $status = $exception->getResponse()?->getStatusCode();

        $creds = (array) ($this->connection->credentials ?? []);
        $refreshToken = (string) ($creds['refresh_token'] ?? '');
        $expiresAt = $creds['expires_at'] ?? null;

        $isExpired = false;
        if ($expiresAt) {
            try {
                $isExpired = now()->greaterThanOrEqualTo(Carbon::parse($expiresAt));
            } catch (\Throwable $e) {
                $isExpired = false;
            }
        }

        // Dropbox long-lived tokens won't expire; short-lived tokens can, if app uses refresh tokens.
        if ($refreshToken === '') {
            // If token revoked/invalid, mark connection and don't retry.
            if ($status === 401) {
                $this->connection->update(['status' => 'error']);
            }
            return false;
        }

        // Only attempt refresh on expiry or auth failure.
        if (!($isExpired || $status === 401)) {
            return false;
        }

        try {
            $resp = Http::asForm()
                ->withBasicAuth($this->appKey, $this->appSecret)
                ->post('https://api.dropboxapi.com/oauth2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$resp->successful()) {
                $this->connection->update(['status' => 'error']);
                return false;
            }

            $body = (array) $resp->json();
            $newAccessToken = (string) ($body['access_token'] ?? '');
            if ($newAccessToken === '') {
                $this->connection->update(['status' => 'error']);
                return false;
            }

            $newCreds = $creds;
            $newCreds['access_token'] = $newAccessToken;

            if (isset($body['expires_in'])) {
                $newCreds['expires_at'] = now()->addSeconds((int) $body['expires_in'])->toIso8601String();
            }

            // Preserve existing refresh token.
            $this->connection->update([
                'credentials' => $newCreds,
                'status' => 'connected',
            ]);
            $this->connection->refresh();

            return true;
        } catch (\Throwable $e) {
            $this->connection->update(['status' => 'error']);
            return false;
        }
    }
}

