<?php

namespace App\Http\Livewire\Admin\Social;

use App\Models\Client;
use App\Models\ContentCalendarItem;
use Carbon\Carbon;
use Livewire\Component;

class ContentCalendar extends Component
{
    public $currentDate;

    public $viewMode = 'month'; // month or week

    public $selectedClient = '';

    public $selectedPlatform = '';

    public $selectedPost = null;

    public $showQuickScheduleModal = false;

    // Quick schedule fields
    public $quick_post_id;

    public $quick_scheduled_date;

    public $quick_scheduled_time;

    protected $queryString = [
        'viewMode' => ['except' => 'month'],
        'selectedClient' => ['except' => ''],
        'selectedPlatform' => ['except' => ''],
    ];

    public function mount()
    {
        $this->currentDate = Carbon::now()->startOfMonth();
    }

    public function previousPeriod()
    {
        if ($this->viewMode === 'month') {
            $this->currentDate = $this->currentDate->copy()->subMonth();
        } else {
            $this->currentDate = $this->currentDate->copy()->subWeek();
        }
    }

    public function nextPeriod()
    {
        if ($this->viewMode === 'month') {
            $this->currentDate = $this->currentDate->copy()->addMonth();
        } else {
            $this->currentDate = $this->currentDate->copy()->addWeek();
        }
    }

    public function today()
    {
        $this->currentDate = Carbon::now()->startOfMonth();
    }

    public function switchView($mode)
    {
        $this->viewMode = $mode;
    }

    public function viewPost($postId)
    {
        $this->selectedPost = ContentCalendarItem::with(['client', 'creator', 'approver'])
            ->findOrFail($postId);
    }

    public function closePostModal()
    {
        $this->selectedPost = null;
    }

    public function openQuickSchedule($postId)
    {
        $post = ContentCalendarItem::findOrFail($postId);

        $this->quick_post_id = $postId;
        $this->quick_scheduled_date = $post->scheduled_for ? $post->scheduled_for->format('Y-m-d') : now()->format('Y-m-d');
        $this->quick_scheduled_time = $post->scheduled_for ? $post->scheduled_for->format('H:i') : '09:00';
        $this->showQuickScheduleModal = true;
    }

    public function closeQuickScheduleModal()
    {
        $this->showQuickScheduleModal = false;
        $this->quick_post_id = null;
    }

    public function quickSchedule()
    {
        $this->validate([
            'quick_scheduled_date' => 'required|date|after_or_equal:today',
            'quick_scheduled_time' => 'required',
        ]);

        $post = ContentCalendarItem::findOrFail($this->quick_post_id);

        $scheduledDateTime = Carbon::parse($this->quick_scheduled_date.' '.$this->quick_scheduled_time);

        $post->schedule($scheduledDateTime);

        session()->flash('success', 'Post scheduled successfully!');
        $this->closeQuickScheduleModal();
    }

    public function unschedulePost($postId)
    {
        $post = ContentCalendarItem::findOrFail($postId);

        $post->update([
            'status' => $post->isApproved() ? 'approved' : 'draft',
            'scheduled_for' => null,
        ]);

        session()->flash('success', 'Post unscheduled.');
    }

    protected function getCalendarDays()
    {
        $start = $this->currentDate->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $this->currentDate->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $days = [];
        $current = $start->copy();

        while ($current <= $end) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return $days;
    }

    protected function getWeekDays()
    {
        $start = $this->currentDate->copy()->startOfWeek(Carbon::SUNDAY);
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i);
        }

        return $days;
    }

    protected function getPostsForDate($date)
    {
        $query = ContentCalendarItem::with(['client', 'creator'])
            ->whereDate('scheduled_for', $date->format('Y-m-d'))
            ->where('content_type', 'social')
            ->whereIn('status', ['scheduled', 'published']);

        if ($this->selectedClient) {
            $query->where('client_id', $this->selectedClient);
        }

        if ($this->selectedPlatform) {
            $query->where('platform', $this->selectedPlatform);
        }

        return $query->orderBy('scheduled_for')->get();
    }

    public function render()
    {
        $days = $this->viewMode === 'month' ? $this->getCalendarDays() : $this->getWeekDays();

        // Get posts for each day
        $postsPerDay = [];
        foreach ($days as $day) {
            $postsPerDay[$day->format('Y-m-d')] = $this->getPostsForDate($day);
        }

        // Stats for current period
        $periodStart = $this->viewMode === 'month'
            ? $this->currentDate->copy()->startOfMonth()
            : $this->currentDate->copy()->startOfWeek();

        $periodEnd = $this->viewMode === 'month'
            ? $this->currentDate->copy()->endOfMonth()
            : $this->currentDate->copy()->endOfWeek();

        $stats = [
            'scheduled' => ContentCalendarItem::where('content_type', 'social')
                ->scheduled()
                ->whereBetween('scheduled_for', [$periodStart, $periodEnd])
                ->count(),
            'published' => ContentCalendarItem::where('content_type', 'social')
                ->published()
                ->whereBetween('published_at', [$periodStart, $periodEnd])
                ->count(),
            'pending_approval' => ContentCalendarItem::where('content_type', 'social')
                ->pendingApproval()
                ->count(),
        ];

        return view('livewire.admin.social.content-calendar', [
            'days' => $days,
            'postsPerDay' => $postsPerDay,
            'clients' => Client::active()->orderBy('company_name')->get(),
            'platforms' => ['facebook', 'instagram', 'linkedin', 'x', 'tiktok', 'pinterest'],
            'stats' => $stats,
        ])->layout('layouts.admin');
    }
}
