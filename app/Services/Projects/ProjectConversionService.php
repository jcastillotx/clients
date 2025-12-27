<?php

namespace App\Services\Projects;

use App\Models\Conversation;
use App\Models\ProjectBudget;
use App\Models\Request as ServiceRequest;
use App\Models\RequestEstimate;
use App\Models\Task;

class ProjectConversionService
{
    public function convert(ServiceRequest $request): array
    {
        $request->loadMissing('client');

        // Ensure a project budget row exists (best-effort).
        ProjectBudget::query()->firstOrCreate(['request_id' => $request->id], [
            'budget_hours' => null,
            'budget_amount' => null,
            'spent_hours' => 0,
            'spent_amount' => 0,
            'is_exceeded' => false,
        ]);

        // Seed tasks from latest estimate if present; otherwise create a starter set.
        $estimate = RequestEstimate::query()->where('request_id', $request->id)->orderByDesc('id')->first();
        $seeded = 0;

        $tasks = (array) ($estimate?->estimate_data['tasks'] ?? []);
        $order = 0;
        foreach ($tasks as $t) {
            if (!is_array($t)) continue;
            $name = trim((string) ($t['name'] ?? ''));
            if ($name === '') continue;

            $task = Task::firstOrCreate(
                ['request_id' => $request->id, 'title' => $name],
                [
                    'description' => (string) ($t['description'] ?? '') ?: null,
                    'status' => 'todo',
                    'priority' => 'normal',
                    'estimated_hours' => (float) (($t['hours_mid'] ?? null) ?: null),
                    'order' => $order++,
                ]
            );
            if ($task->wasRecentlyCreated) $seeded++;
        }

        if ($seeded === 0) {
            foreach (['Kickoff & access', 'Discovery', 'Build', 'QA', 'Launch'] as $title) {
                $task = Task::firstOrCreate(
                    ['request_id' => $request->id, 'title' => $title],
                    ['status' => 'todo', 'priority' => 'normal', 'order' => $order++]
                );
                if ($task->wasRecentlyCreated) $seeded++;
            }
        }

        // Ensure a request-linked conversation exists (client messaging).
        if ($request->client_id) {
            Conversation::query()->firstOrCreate(
                ['client_id' => $request->client_id, 'context_type' => 'request', 'context_id' => $request->id],
                ['title' => 'Request #' . $request->id . ': ' . $request->title, 'is_closed' => false]
            );
        }

        // Mark request in progress if not already.
        if ($request->status !== 'in_progress') {
            $request->update([
                'status' => 'in_progress',
                'started_at' => $request->started_at ?: now(),
            ]);
        }

        return ['ok' => true, 'seeded_tasks' => $seeded];
    }
}

