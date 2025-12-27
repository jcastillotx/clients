<?php

namespace App\Http\Livewire\Onboarding;

use App\Models\OnboardingWorkflow;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OnboardingProgress extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);

        $workflow = OnboardingWorkflow::query()
            ->where('client_id', $user->client_id)
            ->with('tasks')
            ->orderByDesc('id')
            ->first();

        return view('livewire.onboarding.onboarding-progress', [
            'workflow' => $workflow,
        ]);
    }
}

