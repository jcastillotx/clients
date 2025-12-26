<?php

namespace App\Http\Livewire\Client;

use App\Models\NotificationPreference;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsCenter extends Component
{
    public string $tab = 'inbox'; // inbox|preferences

    public array $prefs = []; // category => ['in_app'=>bool,'email'=>bool,'push'=>bool]

    public function mount(): void
    {
        abort_unless(Auth::user(), 403);
        $this->loadPrefs();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['inbox', 'preferences'], true) ? $tab : 'inbox';
    }

    public function markRead(string $id): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $n = DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->findOrFail($id);
        $n->markAsRead();
    }

    public function markUnread(string $id): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $n = DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->findOrFail($id);
        $n->read_at = null;
        $n->save();
    }

    public function markAllRead(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function savePreferences(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        foreach ($this->defaultCategories() as $cat) {
            $row = $this->prefs[$cat] ?? [];
            NotificationPreference::updateOrCreate([
                'user_id' => $user->id,
                'category' => $cat,
            ], [
                'in_app' => (bool) ($row['in_app'] ?? true),
                'email' => (bool) ($row['email'] ?? true),
                'push' => (bool) ($row['push'] ?? false),
            ]);
        }

        session()->flash('success', 'Preferences saved.');
    }

    protected function loadPrefs(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        $existing = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('category');

        $prefs = [];
        foreach ($this->defaultCategories() as $cat) {
            $row = $existing[$cat] ?? null;
            $prefs[$cat] = [
                'in_app' => $row ? (bool) $row->in_app : true,
                'email' => $row ? (bool) $row->email : true,
                'push' => $row ? (bool) $row->push : false,
            ];
        }
        $this->prefs = $prefs;
    }

    protected function defaultCategories(): array
    {
        return [
            'billing',
            'requests',
            'documents',
            'storage',
            'projects',
            'marketing',
            'security',
        ];
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $notifications = DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->latest('created_at')
            ->paginate(20);

        $unread = DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return view('livewire.client.notifications', [
            'notifications' => $notifications,
            'unread' => $unread,
            'categories' => $this->defaultCategories(),
        ]);
    }
}

