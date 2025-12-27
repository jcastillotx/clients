<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Posts</p>
                </div>
                <div class="icon"><i class="fas fa-list"></i></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['pending_approval'] }}</h3>
                    <p>Pending Approval</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['approved'] }}</h3>
                    <p>Approved</p>
                </div>
                <div class="icon"><i class="fas fa-check"></i></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['scheduled'] }}</h3>
                    <p>Scheduled</p>
                </div>
                <div class="icon"><i class="fas fa-calendar"></i></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['published'] }}</h3>
                    <p>Published</p>
                </div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3>{{ $stats['draft'] }}</h3>
                    <p>Drafts</p>
                </div>
                <div class="icon"><i class="fas fa-file"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-stream mr-2"></i>
                Social Media Posts
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.social.posts.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Create New Post
                </a>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <input wire:model.debounce.300ms="search" type="text" class="form-control" placeholder="Search posts...">
                </div>
                <div class="col-md-2">
                    <select wire:model="selectedClient" class="form-control">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="selectedPlatform" class="form-control">
                        <option value="">All Platforms</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform }}">{{ ucfirst($platform) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="selectedStatus" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button wire:click="clearFilters" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i>
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">Platform</th>
                            <th width="20%">Title</th>
                            <th width="15%">Client</th>
                            <th width="25%">Content Preview</th>
                            <th width="10%">Status</th>
                            <th width="10%">Scheduled</th>
                            <th width="10%">Created By</th>
                            <th width="5%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td>
                                    <i class="{{ $post->platform_icon }} fa-2x"></i>
                                </td>
                                <td>
                                    <strong>{{ $post->title }}</strong>
                                    @if($post->campaign_tag)
                                        <br><span class="badge badge-info">{{ $post->campaign_tag }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $post->client->company_name ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;">
                                        {{ Str::limit($post->content_text, 100) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $post->status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $post->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($post->scheduled_for)
                                        {{ $post->scheduled_for->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-muted">Not scheduled</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $post->creator->name ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.social.posts.edit', $post->id) }}" class="btn btn-sm btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button wire:click="duplicatePost({{ $post->id }})" class="btn btn-sm btn-secondary" title="Duplicate">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        @if(in_array($post->status, ['draft', 'failed']))
                                            <button wire:click="deletePost({{ $post->id }})"
                                                    onclick="return confirm('Are you sure you want to delete this post?')"
                                                    class="btn btn-sm btn-danger"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No posts found. <a href="{{ route('admin.social.posts.create') }}">Create your first post</a></p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
