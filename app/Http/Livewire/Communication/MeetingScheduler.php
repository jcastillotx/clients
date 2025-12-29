<?php

namespace App\Http\Livewire\Communication;

use App\Models\Meeting;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class MeetingScheduler extends Component
{
    public ?int $requestId = null;

    public string $meetingType = 'kickoff';

    public string $title = '';

    public string $scheduledAt = '';

    public int $durationMinutes = 30;

    public string $agenda = '';

    public string $meetingLink = '';

    public string $status = 'requested';

    public ?int $editingMeetingId = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->scheduledAt = now()->addDays(2)->format('Y-m-d\\TH:i');
    }

    public function create(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($user->client_id, 403);

        Validator::make([
            'meetingType' => $this->meetingType,
            'title' => $this->title,
            'scheduledAt' => $this->scheduledAt,
            'durationMinutes' => $this->durationMinutes,
        ], [
            'meetingType' => ['required', 'in:kickoff,strategy,review,other'],
            'title' => ['required', 'string', 'max:255'],
            'scheduledAt' => ['required', 'date'],
            'durationMinutes' => ['required', 'integer', 'min:15', 'max:240'],
        ])->validate();

        Meeting::create([
            'client_id' => $user->client_id,
            'request_id' => $this->requestId ?: null,
            'title' => trim($this->title),
            'meeting_type' => $this->meetingType,
            'status' => 'requested',
            'scheduled_at' => $this->scheduledAt ? now()->parse($this->scheduledAt) : null,
            'duration_minutes' => (int) $this->durationMinutes,
            'agenda' => trim($this->agenda) ?: null,
            'created_by' => $user->id,
        ]);

        $this->reset(['requestId', 'meetingType', 'title', 'agenda']);
        $this->meetingType = 'kickoff';
        $this->durationMinutes = 30;
        $this->scheduledAt = now()->addDays(2)->format('Y-m-d\\TH:i');

        session()->flash('success', 'Meeting request submitted.');
    }

    public function edit(int $meetingId): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $m = Meeting::query()->findOrFail($meetingId);
        $this->editingMeetingId = $m->id;
        $this->requestId = $m->request_id;
        $this->meetingType = $m->meeting_type;
        $this->title = $m->title;
        $this->status = $m->status;
        $this->scheduledAt = $m->scheduled_at ? $m->scheduled_at->format('Y-m-d\\TH:i') : '';
        $this->durationMinutes = (int) $m->duration_minutes;
        $this->agenda = (string) ($m->agenda ?? '');
        $this->meetingLink = (string) ($m->meeting_link ?? '');
    }

    public function saveAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        abort_unless($this->editingMeetingId, 422);

        Validator::make([
            'meetingType' => $this->meetingType,
            'title' => $this->title,
            'status' => $this->status,
            'scheduledAt' => $this->scheduledAt,
            'durationMinutes' => $this->durationMinutes,
            'meetingLink' => $this->meetingLink,
        ], [
            'meetingType' => ['required', 'in:kickoff,strategy,review,other'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:requested,scheduled,completed,cancelled'],
            'scheduledAt' => ['nullable', 'date'],
            'durationMinutes' => ['required', 'integer', 'min:15', 'max:240'],
            'meetingLink' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $m = Meeting::query()->findOrFail($this->editingMeetingId);
        $m->update([
            'request_id' => $this->requestId ?: null,
            'meeting_type' => $this->meetingType,
            'title' => trim($this->title),
            'status' => $this->status,
            'scheduled_at' => $this->scheduledAt ? now()->parse($this->scheduledAt) : null,
            'duration_minutes' => (int) $this->durationMinutes,
            'agenda' => trim($this->agenda) ?: null,
            'meeting_link' => trim($this->meetingLink) ?: null,
        ]);

        $this->reset(['editingMeetingId', 'requestId', 'meetingType', 'title', 'status', 'scheduledAt', 'durationMinutes', 'agenda', 'meetingLink']);
        $this->meetingType = 'kickoff';
        $this->status = 'requested';
        $this->durationMinutes = 30;
        $this->scheduledAt = now()->addDays(2)->format('Y-m-d\\TH:i');

        session()->flash('success', 'Meeting updated.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if ($user->isClient()) {
            $requests = ServiceRequest::query()
                ->where('client_id', $user->client_id)
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'title']);

            $meetings = Meeting::query()
                ->where('client_id', $user->client_id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            return view('livewire.communication.meeting-scheduler', [
                'mode' => 'client',
                'requests' => $requests,
                'meetings' => $meetings,
            ]);
        }

        // staff/admin view
        $meetings = Meeting::query()->with('client')->orderByDesc('created_at')->limit(200)->get();

        return view('livewire.communication.meeting-scheduler', [
            'mode' => 'admin',
            'requests' => collect(),
            'meetings' => $meetings,
        ]);
    }
}
