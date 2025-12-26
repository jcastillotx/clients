<x-app-layout>
    <x-slot name="header">Knowledge Base</x-slot>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book mr-1"></i> Categories</h3>
                </div>
                <div class="card-body">
                    <input class="form-control mb-2" placeholder="Search articles..." wire:model.live.debounce.300ms="search">
                    <button class="btn btn-sm btn-outline-secondary mb-2" wire:click="selectCategory(null)">All</button>
                    <div class="list-group">
                        @foreach($categories as $c)
                            <a href="#" class="list-group-item list-group-item-action {{ $categoryId === $c->id ? 'active' : '' }}"
                               wire:click.prevent="selectCategory({{ $c->id }})">
                                {{ $c->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Articles</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="list-group" style="max-height: 520px; overflow:auto;">
                                @forelse($articles as $a)
                                    <a href="#" class="list-group-item list-group-item-action {{ $articleId === $a->id ? 'active' : '' }}"
                                       wire:click.prevent="openArticle({{ $a->id }})">
                                        <div class="font-weight-bold">{{ $a->title }}</div>
                                        <div class="text-muted small">{{ $a->excerpt }}</div>
                                    </a>
                                @empty
                                    <div class="text-muted">No articles found.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-7">
                            @if(!$article)
                                <div class="text-muted">Select an article to read.</div>
                            @else
                                <div class="mb-2">
                                    <div class="text-muted small">{{ $article->category?->name }}</div>
                                    <h4 class="mb-1">{{ $article->title }}</h4>
                                </div>

                                @if($article->video_url)
                                    <div class="mb-3">
                                        <div class="text-muted small mb-1">Video tutorial</div>
                                        <div class="embed-responsive embed-responsive-16by9">
                                            <iframe class="embed-responsive-item" src="{{ $article->video_url }}" allowfullscreen></iframe>
                                        </div>
                                    </div>
                                @endif

                                <div class="border rounded p-3" style="max-height: 380px; overflow:auto;">
                                    {!! nl2br(e($article->body)) !!}
                                </div>

                                <hr>

                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <span class="text-muted">Was this helpful?</span>
                                    <button class="btn btn-sm btn-outline-success" wire:click="submitFeedback(true)">Yes</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="submitFeedback(false)">No</button>
                                </div>

                                <div class="mt-2">
                                    <textarea class="form-control" rows="2" placeholder="Optional feedback..." wire:model.defer="feedbackComment"></textarea>
                                    <small class="text-muted">You can include what you were trying to do and what didn’t work.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

