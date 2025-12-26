<?php

namespace App\Http\Livewire\Client;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseFeedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class KnowledgeBase extends Component
{
    public string $search = '';
    public ?int $categoryId = null;
    public ?int $articleId = null;

    public ?bool $helpful = null;
    public string $feedbackComment = '';

    protected array $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => null],
        'articleId' => ['except' => null],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user(), 403);
    }

    public function selectCategory(?int $id): void
    {
        $this->categoryId = $id;
        $this->articleId = null;
        $this->helpful = null;
        $this->feedbackComment = '';
    }

    public function openArticle(int $id): void
    {
        $this->articleId = $id;
        $this->helpful = null;
        $this->feedbackComment = '';
    }

    public function submitFeedback(bool $wasHelpful): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($this->articleId, 422);

        Validator::make([
            'comment' => $this->feedbackComment,
        ], [
            'comment' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        KnowledgeBaseFeedback::create([
            'article_id' => $this->articleId,
            'user_id' => $user->id,
            'was_helpful' => $wasHelpful,
            'comment' => trim($this->feedbackComment) ?: null,
        ]);

        $this->helpful = $wasHelpful;
        $this->feedbackComment = '';
        session()->flash('success', 'Thanks for the feedback!');
    }

    public function render()
    {
        $categories = KnowledgeBaseCategory::query()->orderBy('sort_order')->orderBy('name')->get();

        $article = null;
        if ($this->articleId) {
            $article = KnowledgeBaseArticle::query()
                ->where('is_published', true)
                ->with('category')
                ->find($this->articleId);
        }

        $articles = KnowledgeBaseArticle::query()
            ->where('is_published', true)
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('excerpt', 'like', '%' . $this->search . '%')
                        ->orWhere('body', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('livewire.client.knowledge-base', compact('categories', 'articles', 'article'));
    }
}

