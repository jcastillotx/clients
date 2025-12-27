<?php

namespace App\Http\Livewire\Research;

use App\Models\IndustryInsight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class IndustryInsights extends Component
{
    public string $industry = '';
    public string $insightType = 'news';
    public string $title = '';
    public string $content = '';
    public string $sourceUrl = '';

    public function add(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        Validator::make([
            'title' => $this->title,
        ], [
            'title' => ['required', 'string', 'max:255'],
        ])->validate();

        IndustryInsight::create([
            'industry' => trim($this->industry) ?: null,
            'insight_type' => $this->insightType,
            'title' => trim($this->title),
            'content' => trim($this->content) ?: null,
            'source_url' => trim($this->sourceUrl) ?: null,
            'published_at' => now(),
        ]);

        $this->reset(['industry', 'insightType', 'title', 'content', 'sourceUrl']);
        $this->insightType = 'news';
        session()->flash('success', 'Insight added.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u, 403);

        $q = IndustryInsight::query()->orderByDesc('published_at')->orderByDesc('id');
        if ($u->isClient() && $u->client?->industry) {
            $q->where(function ($w) use ($u) {
                $w->whereNull('industry')->orWhere('industry', $u->client->industry);
            });
        }
        $insights = $q->limit(100)->get();

        return view('livewire.research.industry-insights', [
            'insights' => $insights,
            'canAdd' => $u->isAdmin() || $u->isStaff(),
        ]);
    }
}

