<?php

namespace App\Http\Livewire\Marketing;

use App\Models\Client;
use App\Models\ContentCalendarItem;
use App\Models\SocialAccount;
use App\Services\Social\SocialMediaPublishingService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class SocialMediaManager extends Component
{
    use WithFileUploads;

    public $clientId;
    public $client;
    public $viewMode = 'calendar';
    public $showCreateModal = false;
    public $selectedMonth;
    public $selectedYear;

    public $postId = null;
    public $content = '';
    public $platforms = [];
    public $scheduledFor = '';
    public $scheduledTime = '';
    public $status = 'draft';
    public $mediaFiles = [];
    public $hashtags = '';
    public $campaignId = null;

    protected $rules = [
        'content' => 'required|string|max:5000',
        'platforms' => 'required|array|min:1',
        'scheduledFor' => 'required|date',
        'scheduledTime' => 'required',
        'status' => 'required|in:draft,pending_approval,approved,scheduled,published',
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function openCreateModal()
    {
        $this->reset(['postId', 'content', 'platforms', 'scheduledFor', 'scheduledTime', 'mediaFiles', 'hashtags']);
        $this->scheduledFor = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '09:00';
        $this->showCreateModal = true;
    }

    public function editPost($postId)
    {
        $post = ContentCalendarItem::where('client_id', $this->clientId)->findOrFail($postId);

        $this->postId = $post->id;
        $this->content = $post->content;
        $this->platforms = $post->platforms ?? [];
        $this->scheduledFor = $post->scheduled_for->format('Y-m-d');
        $this->scheduledTime = $post->scheduled_for->format('H:i');
        $this->status = $post->status;
        $this->hashtags = $post->hashtags ? implode(' ', $post->hashtags) : '';
        $this->campaignId = $post->campaign_id;

        $this->showCreateModal = true;
    }

    public function savePost()
    {
        $this->validate();

        $scheduledDateTime = Carbon::parse($this->scheduledFor . ' ' . $this->scheduledTime);

        $data = [
            'client_id' => $this->clientId,
            'content' => $this->content,
            'platforms' => $this->platforms,
            'scheduled_for' => $scheduledDateTime,
            'status' => $this->status,
            'hashtags' => $this->hashtags ? explode(' ', $this->hashtags) : null,
            'campaign_id' => $this->campaignId,
            'created_by' => auth()->id(),
        ];

        if ($this->mediaFiles) {
            $mediaUrls = [];
            foreach ($this->mediaFiles as $file) {
                $path = $file->store('social-media', 'public');
                $mediaUrls[] = asset('storage/' . $path);
            }
            $data['media_urls'] = $mediaUrls;
        }

        if ($this->postId) {
            $post = ContentCalendarItem::findOrFail($this->postId);
            $post->update($data);
            session()->flash('message', 'Post updated successfully!');
        } else {
            ContentCalendarItem::create($data);
            session()->flash('message', 'Post created successfully!');
        }

        $this->showCreateModal = false;
        $this->reset(['postId', 'content', 'platforms', 'scheduledFor', 'scheduledTime', 'mediaFiles', 'hashtags']);
    }

    public function deletePost($postId)
    {
        ContentCalendarItem::where('client_id', $this->clientId)
            ->findOrFail($postId)
            ->delete();

        session()->flash('message', 'Post deleted successfully!');
    }

    public function approvePost($postId)
    {
        $post = ContentCalendarItem::where('client_id', $this->clientId)->findOrFail($postId);
        $post->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('message', 'Post approved!');
    }

    public function requestChanges($postId, $feedback)
    {
        $post = ContentCalendarItem::where('client_id', $this->clientId)->findOrFail($postId);
        $post->update([
            'status' => 'revision_requested',
            'revision_notes' => $feedback,
        ]);

        session()->flash('message', 'Changes requested.');
    }

    public function publishNow($postId)
    {
        $post = ContentCalendarItem::where('client_id', $this->clientId)->findOrFail($postId);

        if ($post->status !== 'approved') {
            session()->flash('error', 'Only approved posts can be published.');
            return;
        }

        try {
            foreach ($post->platforms as $platform) {
                $account = SocialAccount::where('client_id', $this->clientId)
                    ->where('platform', $platform)
                    ->where('is_connected', true)
                    ->first();

                if (!$account) {
                    throw new \Exception("No connected account for {$platform}");
                }

                $service = new SocialMediaPublishingService($account);
                $result = $service->publishPost(
                    $post->content,
                    $post->media_urls ?? []
                );

                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'post_id' => $result['id'] ?? null,
                ]);
            }

            session()->flash('message', 'Post published successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to publish: ' . $e->getMessage());
        }
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear = $date->year;
    }

    public function render()
    {
        $connectedAccounts = SocialAccount::where('client_id', $this->clientId)
            ->where('is_connected', true)
            ->get();

        if ($this->viewMode === 'calendar') {
            $posts = $this->getMonthPosts();
            $calendarData = $this->getCalendarData($posts);

            return view('livewire.marketing.social-media-manager', [
                'connectedAccounts' => $connectedAccounts,
                'posts' => $posts,
                'calendarData' => $calendarData,
            ]);
        } else {
            $posts = ContentCalendarItem::where('client_id', $this->clientId)
                ->orderBy('scheduled_for', 'desc')
                ->paginate(20);

            return view('livewire.marketing.social-media-manager', [
                'connectedAccounts' => $connectedAccounts,
                'posts' => $posts,
            ]);
        }
    }

    protected function getMonthPosts()
    {
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        return ContentCalendarItem::where('client_id', $this->clientId)
            ->whereBetween('scheduled_for', [$startDate, $endDate])
            ->orderBy('scheduled_for')
            ->get();
    }

    protected function getCalendarData($posts)
    {
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $daysInMonth = $endDate->day;
        $firstDayOfWeek = $startDate->dayOfWeek;

        $calendar = [];
        $week = [];

        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $week[] = null;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, $day);
            $dayPosts = $posts->filter(function ($post) use ($date) {
                return $post->scheduled_for->isSameDay($date);
            });

            $week[] = [
                'date' => $date,
                'posts' => $dayPosts,
            ];

            if (count($week) === 7) {
                $calendar[] = $week;
                $week = [];
            }
        }

        if (!empty($week)) {
            while (count($week) < 7) {
                $week[] = null;
            }
            $calendar[] = $week;
        }

        return $calendar;
    }
}
