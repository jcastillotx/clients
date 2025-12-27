<?php

namespace App\Http\Livewire\Communication;

use App\Services\AI\CommunicationAssistantService;
use Livewire\Component;

class EmailDraftAssistant extends Component
{
    public string $purpose = 'request_update';

    public string $tone = 'friendly'; // formal|friendly|urgent

    public string $contextJson = '';

    public string $subject = '';

    public string $body = '';

    public array $bullets = [];

    public function draft(CommunicationAssistantService $svc): void
    {
        $context = [];
        $decoded = json_decode($this->contextJson, true);
        if (is_array($decoded)) {
            $context = $decoded;
        }

        $res = $svc->draftEmail($context, $this->purpose, $this->tone, [
            'provider' => 'openai',
            'timeout' => 90,
        ]);

        $this->subject = (string) ($res['subject'] ?? '');
        $this->body = (string) ($res['body'] ?? '');
        $this->bullets = is_array($res['short_bullets'] ?? null) ? $res['short_bullets'] : [];
    }

    public function improve(CommunicationAssistantService $svc): void
    {
        if (trim($this->body) === '') {
            return;
        }
        $res = $svc->improveWriting($this->body, [
            'provider' => 'openai',
            'tone' => $this->tone,
        ]);
        $this->body = (string) ($res['improved_text'] ?? $this->body);
    }

    public function render()
    {
        return view('livewire.communication.email-assistant');
    }
}
