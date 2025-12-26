<x-app-layout>
    <x-slot name="header">Notifications</x-slot>

    <div class="card">
        <div class="card-header p-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'inbox' ? 'active' : '' }}" wire:click.prevent="setTab('inbox')">
                        Inbox
                        @if($unread > 0)
                            <span class="badge badge-danger ml-1">{{ $unread }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $tab === 'preferences' ? 'active' : '' }}" wire:click.prevent="setTab('preferences')">
                        Preferences
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            @if($tab === 'inbox')
                <div class="d-flex justify-content-between mb-2">
                    <div class="text-muted">In-app notifications (mark read/unread).</div>
                    <button class="btn btn-sm btn-outline-primary" wire:click="markAllRead">
                        <i class="fas fa-check mr-1"></i> Mark all read
                    </button>
                </div>

                <div class="list-group">
                    @forelse($notifications as $n)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="badge badge-{{ $n->read_at ? 'secondary' : 'warning' }}">{{ $n->read_at ? 'read' : 'new' }}</span>
                                    <span class="ml-2 font-weight-bold">{{ class_basename($n->type) }}</span>
                                    <div class="text-muted small mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                                </div>
                                <div>
                                    @if($n->read_at)
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="markUnread('{{ $n->id }}')">Unread</button>
                                    @else
                                        <button class="btn btn-sm btn-outline-success" wire:click="markRead('{{ $n->id }}')">Read</button>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-2">
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($n->data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No notifications.</div>
                    @endforelse
                </div>

                <div class="mt-3">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    Preferences control how you want to receive different categories. Push requires PWA setup and browser permission.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>In-app</th>
                                <th>Email</th>
                                <th>Push</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                                <tr>
                                    <td class="font-weight-bold">{{ ucfirst($cat) }}</td>
                                    <td><input type="checkbox" wire:model.defer="prefs.{{ $cat }}.in_app"></td>
                                    <td><input type="checkbox" wire:model.defer="prefs.{{ $cat }}.email"></td>
                                    <td><input type="checkbox" wire:model.defer="prefs.{{ $cat }}.push"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button class="btn btn-primary" wire:click="savePreferences">
                    <i class="fas fa-save mr-1"></i> Save preferences
                </button>
            @endif
        </div>
    </div>
</x-app-layout>

