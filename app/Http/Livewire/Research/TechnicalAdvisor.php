<?php

namespace App\Http\Livewire\Research;

use App\Services\AI\TechnicalAdvisorService;
use Livewire\Component;

class TechnicalAdvisor extends Component
{
    public string $mode = 'architecture'; // architecture|stack

    public string $inputJson = '{"overview":"","architecture":"","components":[],"data_flows":[],"security":"","scaling":"","availability":"","constraints":""}';

    public array $result = [];
    public ?string $error = null;

    public function run(TechnicalAdvisorService $svc): void
    {
        $this->error = null;
        $this->result = [];

        try {
            $payload = json_decode($this->inputJson, true);
            if (!is_array($payload)) {
                $payload = ['text' => $this->inputJson];
            }

            $this->result = $this->mode === 'stack'
                ? $svc->technologyRecommendations($payload)
                : $svc->architectureReview($payload);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.research.technical-advisor')
            ->layout('layouts.app', ['title' => 'Technical Advisor']);
    }
}

