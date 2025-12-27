<?php

namespace App\Http\Livewire\Marketing;

use App\Models\WebsiteAudit;
use Livewire\Component;
use Livewire\WithPagination;

class AuditResults extends Component
{
    use WithPagination;

    public ?int $client_id = null;

    public function mount(): void
    {
        $this->client_id = auth()->user()?->client_id;
    }

    public function render()
    {
        $audits = WebsiteAudit::query()
            ->when($this->client_id !== null, fn ($q) => $q->where('client_id', $this->client_id))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.marketing.audit-results', compact('audits'));
    }
}
