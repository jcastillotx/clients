<?php

namespace App\Http\Livewire\Marketing;

use App\Jobs\Marketing\RunWebsiteAuditJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class WebsiteAuditor extends Component
{
    public string $website_url = '';

    public int $max_pages = 25;

    public bool $use_ai = true;

    public function runAudit(): void
    {
        Validator::make([
            'website_url' => $this->website_url,
            'max_pages' => $this->max_pages,
        ], [
            'website_url' => ['required', 'string', 'max:2048'],
            'max_pages' => ['required', 'integer', 'min:1', 'max:500'],
        ])->validate();

        RunWebsiteAuditJob::dispatch($this->website_url, [
            'client_id' => Auth::user()?->client_id,
            'max_pages' => $this->max_pages,
            'use_ai' => $this->use_ai,
        ]);

        session()->flash('success', 'Website audit queued. Results will appear in the Audit Results page once complete.');
    }

    public function render()
    {
        return view('livewire.marketing.website-auditor');
    }
}
