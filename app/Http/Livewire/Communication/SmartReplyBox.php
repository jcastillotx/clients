<?php

namespace App\Http\Livewire\Communication;

use App\Services\AI\CommunicationAssistantService;
use Livewire\Component;

class SmartReplyBox extends Component
{
    public string $clientMessage = '';

    public string $contextJson = ''; // optional JSON string

    /** @var array<int, array{title:string, text:string}> */
    public array $replies = [];

    public string $recommendedTone = '';

    public function suggest(CommunicationAssistantService $svc): void
    {
        $msg = trim($this->clientMessage);
        if ($msg === '') {
            session()->flash('error', 'No message to reply to.');

            return;
        }

        $context = [];
        $decoded = json_decode($this->contextJson, true);
        if (is_array($decoded)) {
            $context = $decoded;
        }

        $res = $svc->draftResponse($msg, $context, [], [
            'provider' => 'openai',
            'timeout' => 60,
        ]);

        $this->recommendedTone = (string) ($res['recommended_tone'] ?? '');
        $raw = $res['replies'] ?? [];
        $this->replies = is_array($raw) ? array_slice(array_map(function ($r) {
            return [
                'title' => (string) ($r['title'] ?? 'Reply'),
                'text' => (string) ($r['text'] ?? ''),
            ];
        }, $raw), 0, 3) : [];
    }

    public function choose(int $idx): void
    {
        $text = (string) ($this->replies[$idx]['text'] ?? '');
        if ($text === '') {
            return;
        }

        $this->dispatch('smart-reply-selected', text: $text);
    }

    public function render()
    {
        return view('livewire.communication.smart-reply');
    }
}
