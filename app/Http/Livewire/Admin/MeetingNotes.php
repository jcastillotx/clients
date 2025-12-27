<?php

namespace App\Http\Livewire\Admin;

use App\Jobs\Ai\TranscribeMeetingJob;
use App\Models\AiTask;
use Livewire\Component;
use Livewire\WithFileUploads;

class MeetingNotes extends Component
{
    use WithFileUploads;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $audio;

    public string $purpose = '';

    public string $participants = ''; // comma separated

    public ?AiTask $latestTask = null;

    public ?array $output = null;

    public function queueTranscription(): void
    {
        if (! $this->audio) {
            session()->flash('error', 'Please upload an audio file.');

            return;
        }

        $this->validate([
            'audio' => ['file', 'max:51200'], // 50MB
            'purpose' => ['nullable', 'string', 'max:500'],
            'participants' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $this->audio->store('meetings', 'attachments');

        $participants = array_values(array_filter(array_map('trim', explode(',', $this->participants))));

        TranscribeMeetingJob::dispatch($path, [
            'purpose' => trim($this->purpose) ?: null,
            'participants' => $participants,
        ]);

        $this->audio = null;
        session()->flash('success', 'Meeting transcription queued.');
        $this->refreshLatest();
    }

    public function refreshLatest(): void
    {
        $this->latestTask = AiTask::query()
            ->where('task_type', 'meeting_notes')
            ->orderByDesc('id')
            ->first();

        $this->output = $this->latestTask && is_array($this->latestTask->output_data) ? $this->latestTask->output_data : null;
    }

    public function render()
    {
        $this->refreshLatest();

        return view('livewire.admin.meeting-notes', [
            'latestTask' => $this->latestTask,
            'output' => $this->output,
        ])->layout('layouts.admin', ['title' => 'Meeting Notes']);
    }
}
