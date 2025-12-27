<?php

namespace App\Http\Livewire\Research;

use App\Models\BrandCompetitor;
use App\Models\CompetitorMonitoring;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class CompetitorMonitor extends Component
{
    public string $competitorName = '';

    public string $websiteUrl = '';

    public function addCompetitor(): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        Validator::make([
            'competitorName' => $this->competitorName,
            'websiteUrl' => $this->websiteUrl,
        ], [
            'competitorName' => ['required', 'string', 'max:255'],
            'websiteUrl' => ['nullable', 'string', 'max:2048'],
        ])->validate();

        BrandCompetitor::create([
            'client_id' => $u->client_id,
            'competitor_name' => trim($this->competitorName),
            'website_url' => trim($this->websiteUrl) ?: null,
            'is_active' => true,
            'meta' => null,
        ]);

        $this->reset(['competitorName', 'websiteUrl']);
        session()->flash('success', 'Competitor added.');
    }

    public function checkNow(int $competitorId): void
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $c = BrandCompetitor::query()
            ->where('client_id', $u->client_id)
            ->findOrFail($competitorId);

        $url = (string) ($c->website_url ?? '');
        $changes = [
            'ok' => false,
            'url' => $url,
            'status' => null,
            'changed' => null,
            'hash' => null,
        ];

        if ($url !== '') {
            try {
                $resp = Http::timeout(10)->get($url);
                $body = (string) $resp->body();
                $hash = hash('sha256', Str::limit($body, 500000, ''));
                $last = (string) (($c->meta['last_hash'] ?? '') ?: '');
                $changes['ok'] = $resp->successful();
                $changes['status'] = $resp->status();
                $changes['hash'] = $hash;
                $changes['changed'] = $last !== '' ? ($hash !== $last) : null;

                $c->update([
                    'meta' => array_merge((array) ($c->meta ?? []), [
                        'last_hash' => $hash,
                        'last_checked_at' => now()->toISOString(),
                    ]),
                ]);
            } catch (\Throwable $e) {
                $changes['ok'] = false;
                $changes['error'] = $e->getMessage();
            }
        }

        CompetitorMonitoring::create([
            'competitor_id' => $c->id,
            'monitored_at' => now(),
            'changes_detected' => $changes,
            'alert_sent' => false,
        ]);

        session()->flash('success', 'Check recorded.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && $u->isClient(), 403);

        $competitors = BrandCompetitor::query()
            ->where('client_id', $u->client_id)
            ->orderByDesc('id')
            ->get();

        return view('livewire.research.competitor-monitor', [
            'competitors' => $competitors,
        ]);
    }
}
