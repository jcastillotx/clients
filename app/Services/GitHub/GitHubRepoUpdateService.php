<?php

namespace App\Services\GitHub;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitHubRepoUpdateService
{
    protected function client(string $token): PendingRequest
    {
        return Http::baseUrl('https://api.github.com')
            ->asJson()
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                // Some GitHub endpoints behave better with a UA present.
                'User-Agent' => 'laravel-admin-updater',
            ])
            ->withToken($token)
            ->timeout(20);
    }

    /**
     * Returns latest commit SHA for a branch/ref.
     *
     * @return array{sha: string, html_url: string|null}
     */
    public function latestCommit(string $owner, string $repo, string $ref, string $token): array
    {
        $res = $this->client($token)->get("/repos/{$owner}/{$repo}/commits/{$ref}");

        if (! $res->successful()) {
            throw new \RuntimeException('GitHub commit lookup failed (HTTP '.$res->status().').');
        }

        $sha = (string) ($res->json('sha') ?? '');
        if ($sha === '') {
            throw new \RuntimeException('GitHub commit lookup returned no SHA.');
        }

        return [
            'sha' => $sha,
            'html_url' => $res->json('html_url'),
        ];
    }

    /**
     * Compare two refs: base...head
     *
     * @return array{
     *   ahead_by:int,
     *   behind_by:int,
     *   status:string|null,
     *   html_url:string|null
     * }
     */
    public function compare(string $owner, string $repo, string $base, string $head, string $token): array
    {
        $res = $this->client($token)->get("/repos/{$owner}/{$repo}/compare/{$base}...{$head}");

        if (! $res->successful()) {
            throw new \RuntimeException('GitHub compare failed (HTTP '.$res->status().').');
        }

        return [
            'ahead_by' => (int) ($res->json('ahead_by') ?? 0),
            'behind_by' => (int) ($res->json('behind_by') ?? 0),
            'status' => $res->json('status'),
            'html_url' => $res->json('html_url'),
        ];
    }

    /**
     * Dispatch a workflow via workflow_dispatch.
     *
     * @param  array<string, scalar|null>  $inputs
     */
    public function dispatchWorkflow(string $owner, string $repo, string $workflowFile, string $ref, array $inputs, string $token): void
    {
        $res = $this->client($token)->post("/repos/{$owner}/{$repo}/actions/workflows/{$workflowFile}/dispatches", [
            'ref' => $ref,
            'inputs' => $inputs,
        ]);

        // GitHub returns 204 No Content on success.
        if ($res->status() !== 204) {
            throw new \RuntimeException('GitHub workflow dispatch failed (HTTP '.$res->status().').');
        }
    }

    /**
     * Returns the most recent workflow run for the given workflow file.
     *
     * @return array{
     *   id:int,
     *   status:string|null,
     *   conclusion:string|null,
     *   html_url:string|null,
     *   created_at:string|null,
     *   updated_at:string|null
     * }|null
     */
    public function latestWorkflowRun(string $owner, string $repo, string $workflowFile, ?string $branch, string $token): ?array
    {
        $query = [
            'per_page' => 1,
        ];
        if (! empty($branch)) {
            $query['branch'] = $branch;
        }

        // We only care about runs triggered via the admin "Update now" button.
        $query['event'] = 'workflow_dispatch';

        $res = $this->client($token)->get("/repos/{$owner}/{$repo}/actions/workflows/{$workflowFile}/runs", $query);

        if (! $res->successful()) {
            throw new \RuntimeException('GitHub workflow runs lookup failed (HTTP '.$res->status().').');
        }

        $run = $res->json('workflow_runs.0');
        if (! is_array($run)) {
            return null;
        }

        return [
            'id' => (int) ($run['id'] ?? 0),
            'status' => $run['status'] ?? null,
            'conclusion' => $run['conclusion'] ?? null,
            'html_url' => $run['html_url'] ?? null,
            'created_at' => $run['created_at'] ?? null,
            'updated_at' => $run['updated_at'] ?? null,
        ];
    }
}

