<?php

namespace App\Services\Marketing;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadNurtureSequence;
use App\Models\User;

class LeadManagementService
{
    /**
     * @param array<string,mixed> $data
     */
    public function captureLead(array $data, string $source): Lead
    {
        /** @var Lead $lead */
        $lead = Lead::create([
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'source' => $source,
            'status' => 'new',
            'score' => null,
            'meta' => $data['meta'] ?? null,
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'capture',
            'description' => "Lead captured from source: {$source}",
            'meta' => ['payload' => $data],
        ]);

        $lead->update(['score' => $this->scoreLead($lead)]);
        return $lead;
    }

    public function scoreLead(Lead $lead): int
    {
        // Basic heuristic scoring. Production would incorporate behavioral + engagement.
        $score = 0;
        if ($lead->email) $score += 20;
        if ($lead->phone) $score += 15;
        if ($lead->company) $score += 15;
        if ($lead->source) $score += 10;

        $activityCount = $lead->activities()->count();
        $score += min(40, $activityCount * 5);

        return max(0, min(100, $score));
    }

    public function assignLead(Lead $lead, User $user): Lead
    {
        $lead->update(['assigned_to' => $user->id]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'assign',
            'description' => "Lead assigned to user #{$user->id}",
        ]);

        return $lead;
    }

    public function nurtureLead(Lead $lead, LeadNurtureSequence $campaign): void
    {
        // MVP scaffold: record that nurture sequence was applied.
        LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => 'nurture_start',
            'description' => "Nurture sequence started: {$campaign->sequence_name}",
            'meta' => ['sequence_id' => $campaign->id, 'steps' => $campaign->steps],
        ]);
    }
}

