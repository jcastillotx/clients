<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Repo Update Checker / Trigger
    |--------------------------------------------------------------------------
    |
    | Used by the Admin -> System Settings -> Updates tab.
    |
    | - "current_sha" should be set at build/deploy time (recommended).
    |   Example: export APP_BUILD_SHA=$(git rev-parse HEAD) in CI.
    |
    */

    'owner' => env('GITHUB_UPDATES_OWNER', ''),
    'repo' => env('GITHUB_UPDATES_REPO', ''),

    // The branch you consider "latest production code" (e.g. main).
    'branch' => env('GITHUB_UPDATES_BRANCH', 'main'),

    // Workflow file name (must exist under .github/workflows/)
    'workflow' => env('GITHUB_UPDATES_WORKFLOW', 'deploy.yml'),

    // Fine-grained PAT or GitHub App token with:
    // - Contents: Read (for checking)
    // - Actions: Read/Write (for workflow dispatch)
    'token' => env('GITHUB_UPDATES_TOKEN', ''),

    // The currently deployed build SHA (ideally injected by CI/CD).
    'current_sha' => env('APP_BUILD_SHA', ''),
];

