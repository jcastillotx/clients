<div class="container-fluid">
    <!-- Header Stats -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-calendar-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Scheduled This {{ ucfirst($viewMode) }}</span>
                    <span class="info-box-number">{{ $stats['scheduled'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-paper-plane"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Published This {{ ucfirst($viewMode) }}</span>
                    <span class="info-box-number">{{ $stats['published'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Approval</span>
                    <span class="info-box-number">{{ $stats['pending_approval'] }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Content Calendar
                    </h3>
                </div>
                <div class="col-md-4 text-center">
                    <div class="btn-group">
                        <button wire:click="previousPeriod" class="btn btn-sm btn-default">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button wire:click="today" class="btn btn-sm btn-default">
                            Today
                        </button>
                        <button wire:click="nextPeriod" class="btn btn-sm btn-default">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <h5 class="mb-0 mt-2">
                        {{ $currentDate->format('F Y') }}
                    </h5>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group" role="group">
                        <button wire:click="switchView('month')" class="btn btn-sm {{ $viewMode === 'month' ? 'btn-primary' : 'btn-default' }}">
                            Month
                        </button>
                        <button wire:click="switchView('week')" class="btn btn-sm {{ $viewMode === 'week' ? 'btn-primary' : 'btn-default' }}">
                            Week
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-3 border-bottom">
                <div class="row">
                    <div class="col-md-4">
                        <select wire:model="selectedClient" class="form-control form-control-sm">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select wire:model="selectedPlatform" class="form-control form-control-sm">
                            <option value="">All Platforms</option>
                            @foreach($platforms as $platform)
                                <option value="{{ $platform }}">{{ ucfirst($platform) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="{{ route('admin.social.posts.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus mr-1"></i>
                            New Post
                        </a>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center" width="14.28%">Sunday</th>
                            <th class="text-center" width="14.28%">Monday</th>
                            <th class="text-center" width="14.28%">Tuesday</th>
                            <th class="text-center" width="14.28%">Wednesday</th>
                            <th class="text-center" width="14.28%">Thursday</th>
                            <th class="text-center" width="14.28%">Friday</th>
                            <th class="text-center" width="14.28%">Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $weeks = $days->chunk(7);
                        @endphp
                        @foreach($weeks as $week)
                            <tr style="height: 150px;">
                                @foreach($week as $day)
                                    <td class="align-top p-2 {{ $day->isToday() ? 'bg-info-light' : '' }} {{ $day->month !== $currentDate->month ? 'bg-light text-muted' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="font-weight-bold {{ $day->isToday() ? 'text-primary' : '' }}">
                                                {{ $day->day }}
                                            </span>
                                            @php
                                                $dayPosts = $postsPerDay[$day->format('Y-m-d')] ?? collect();
                                            @endphp
                                            @if($dayPosts->count() > 0)
                                                <span class="badge badge-primary badge-pill">{{ $dayPosts->count() }}</span>
                                            @endif
                                        </div>

                                        <!-- Posts for this day -->
                                        <div style="max-height: 100px; overflow-y: auto;">
                                            @foreach($dayPosts as $post)
                                                <div class="mb-1">
                                                    <div wire:click="viewPost({{ $post->id }})"
                                                         class="small p-1 rounded cursor-pointer {{ $post->status === 'published' ? 'bg-success' : 'bg-primary' }} text-white"
                                                         style="cursor: pointer; font-size: 0.75rem;">
                                                        <i class="{{ $post->platform_icon }} mr-1"></i>
                                                        <strong>{{ $post->scheduled_for->format('g:i A') }}</strong>
                                                        <div class="text-truncate">{{ Str::limit($post->title, 30) }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Post Detail Modal -->
    @if($selectedPost)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-{{ $selectedPost->status_color }}">
                        <h5 class="modal-title">
                            <i class="{{ $selectedPost->platform_icon }} mr-2"></i>
                            {{ $selectedPost->title }}
                        </h5>
                        <button wire:click="closePostModal" type="button" class="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Post Content -->
                        <div class="mb-3">
                            <label class="font-weight-bold">Content:</label>
                            <div class="p-3 bg-light rounded">
                                <div style="white-space: pre-wrap;">{{ $selectedPost->content_text }}</div>
                            </div>
                        </div>

                        @if($selectedPost->hashtags)
                            <div class="mb-3">
                                <label class="font-weight-bold">Hashtags:</label>
                                <div>
                                    @foreach(explode(' ', $selectedPost->hashtags) as $hashtag)
                                        @if(trim($hashtag))
                                            <span class="badge badge-secondary">{{ $hashtag }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Client:</strong> {{ $selectedPost->client->company_name }}<br>
                                <strong>Platform:</strong> {{ ucfirst($selectedPost->platform) }}<br>
                                <strong>Status:</strong> <span class="badge badge-{{ $selectedPost->status_color }}">{{ ucfirst(str_replace('_', ' ', $selectedPost->status)) }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Created By:</strong> {{ $selectedPost->creator->name ?? 'N/A' }}<br>
                                <strong>Scheduled:</strong> {{ $selectedPost->scheduled_for ? $selectedPost->scheduled_for->format('M d, Y g:i A') : 'Not scheduled' }}<br>
                                @if($selectedPost->published_at)
                                    <strong>Published:</strong> {{ $selectedPost->published_at->format('M d, Y g:i A') }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @if($selectedPost->isScheduled())
                            <button wire:click="unschedulePost({{ $selectedPost->id }})" class="btn btn-warning">
                                <i class="fas fa-times mr-1"></i>
                                Unschedule
                            </button>
                        @elseif($selectedPost->isApproved())
                            <button wire:click="openQuickSchedule({{ $selectedPost->id }})" class="btn btn-primary">
                                <i class="fas fa-calendar-plus mr-1"></i>
                                Schedule
                            </button>
                        @endif
                        <a href="{{ route('admin.social.posts.edit', $selectedPost->id) }}" class="btn btn-info">
                            <i class="fas fa-edit mr-1"></i>
                            Edit
                        </a>
                        <button wire:click="closePostModal" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Schedule Modal -->
    @if($showQuickScheduleModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Schedule Post</h5>
                        <button wire:click="closeQuickScheduleModal" type="button" class="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input wire:model.defer="quick_scheduled_date" type="date" class="form-control @error('quick_scheduled_date') is-invalid @enderror">
                            @error('quick_scheduled_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Time <span class="text-danger">*</span></label>
                            <input wire:model.defer="quick_scheduled_time" type="time" class="form-control @error('quick_scheduled_time') is-invalid @enderror">
                            @error('quick_scheduled_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeQuickScheduleModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="quickSchedule" class="btn btn-primary">
                            <i class="fas fa-calendar-check mr-1"></i>
                            Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .bg-info-light {
        background-color: #d1ecf1 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endpush
