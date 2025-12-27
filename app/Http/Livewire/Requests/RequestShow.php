<?php

namespace App\Http\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Collection;
use Livewire\Component;

class RequestShow extends Component
{
    public ServiceRequest $request;

    /** @var \Illuminate\Support\Collection<int, \App\Models\ActivityLog> */
    public Collection $statusHistory;

    public function mount(ServiceRequest $request): void
    {
        $this->authorizeClientAccess($request);

        $this->request = $request->load([
            'client',
            'creator',
            'assignee',
            'attachments.uploader',
        ]);

        $this->loadStatusHistory();
    }

    protected function loadStatusHistory(): void
    {
        $this->statusHistory = ActivityLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', $this->request->id)
            ->whereNotNull('event')
            ->latest()
            ->take(20)
            ->get();
    }

    protected function authorizeClientAccess(ServiceRequest $request): void
    {
        $user = auth()->user();

        if ($user->isClient() && $request->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.requests.show');
    }
}
