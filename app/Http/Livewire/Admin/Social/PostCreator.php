<?php

namespace App\Http\Livewire\Admin\Social;

use App\Models\Client;
use App\Models\ContentCalendarItem;
use App\Services\AI\SocialMediaAIService;
use Livewire\Component;
use Livewire\WithFileUploads;

class PostCreator extends Component
{
    use WithFileUploads;

    // Post fields
    public $client_id;
    public $title = '';
    public $platform = 'facebook';
    public $content_text = '';
    public $hashtags = '';
    public $campaign_tag = '';
    public $media_urls = [];
    public $scheduled_for;

    // AI fields
    public $ai_prompt = '';
    public $ai_tone = 'professional';
    public $ai_include_hashtags = true;
    public $ai_include_emoji = true;
    public $ai_include_cta = true;
    public $ai_target_audience = '';
    public $ai_keywords = '';
    public $ai_generating = false;
    public $ai_variations = [];
    public $show_ai_panel = false;

    // Media upload
    public $uploaded_media = [];

    // UI state
    public $character_count = 0;
    public $character_limit = 2000;
    public $saving = false;

    protected $rules = [
        'client_id' => 'required|exists:clients,id',
        'title' => 'required|string|max:255',
        'platform' => 'required|in:facebook,instagram,linkedin,x,twitter,tiktok,pinterest',
        'content_text' => 'required|string',
        'hashtags' => 'nullable|string',
        'campaign_tag' => 'nullable|string|max:100',
        'scheduled_for' => 'nullable|date|after:now',
    ];

    public function mount()
    {
        $this->updateCharacterLimit();
    }

    public function updatedPlatform()
    {
        $this->updateCharacterLimit();
    }

    public function updatedContentText()
    {
        $this->character_count = mb_strlen($this->content_text);
    }

    protected function updateCharacterLimit()
    {
        $this->character_limit = match($this->platform) {
            'x', 'twitter' => 280,
            'facebook' => 63206,
            'instagram' => 2200,
            'linkedin' => 3000,
            'tiktok' => 2200,
            default => 2000
        };
    }

    public function toggleAIPanel()
    {
        $this->show_ai_panel = !$this->show_ai_panel;
    }

    public function generateWithAI()
    {
        $this->validate([
            'ai_prompt' => 'required|string|min:10',
        ]);

        $this->ai_generating = true;
        $this->ai_variations = [];

        try {
            $aiService = app(SocialMediaAIService::class);

            $options = [
                'tone' => $this->ai_tone,
                'include_hashtags' => $this->ai_include_hashtags,
                'include_emoji' => $this->ai_include_emoji,
                'include_cta' => $this->ai_include_cta,
            ];

            if ($this->ai_target_audience) {
                $options['target_audience'] = $this->ai_target_audience;
            }

            if ($this->ai_keywords) {
                $options['keywords'] = explode(',', $this->ai_keywords);
            }

            // Get client for brand voice
            if ($this->client_id) {
                $client = Client::find($this->client_id);
                if ($client && isset($client->meta['brand_voice'])) {
                    $options['brand_voice'] = $client->meta['brand_voice'];
                }
            }

            // Generate 3 variations
            $this->ai_variations = $aiService->generateVariations(
                $this->ai_prompt,
                $this->platform,
                3,
                $options
            );

            session()->flash('ai_success', 'Generated 3 AI variations! Select one below.');
        } catch (\Exception $e) {
            session()->flash('ai_error', 'AI generation failed: ' . $e->getMessage());
        } finally {
            $this->ai_generating = false;
        }
    }

    public function useAIVariation($index)
    {
        if (isset($this->ai_variations[$index])) {
            $variation = $this->ai_variations[$index];

            $this->content_text = $variation['content'];

            if (!empty($variation['hashtags'])) {
                $this->hashtags = implode(' ', array_map(fn($h) => "#{$h}", $variation['hashtags']));
            }

            if (empty($this->title)) {
                $this->title = substr($variation['content'], 0, 100) . (strlen($variation['content']) > 100 ? '...' : '');
            }

            $this->character_count = mb_strlen($this->content_text);

            session()->flash('success', 'AI content applied! You can edit it before saving.');
            $this->show_ai_panel = false;
        }
    }

    public function generateHashtags()
    {
        if (empty($this->content_text)) {
            session()->flash('error', 'Please write some content first.');
            return;
        }

        try {
            $aiService = app(SocialMediaAIService::class);
            $hashtags = $aiService->generateHashtags($this->content_text, $this->platform, 5);

            $this->hashtags = implode(' ', array_map(fn($h) => "#{$h}", $hashtags));

            session()->flash('success', 'Hashtags generated!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate hashtags: ' . $e->getMessage());
        }
    }

    public function analyzeContent()
    {
        if (empty($this->content_text)) {
            session()->flash('error', 'Please write some content first.');
            return;
        }

        try {
            $aiService = app(SocialMediaAIService::class);
            $analysis = $aiService->analyzeAndImprove($this->content_text);

            $this->emit('showAnalysis', $analysis);
        } catch (\Exception $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function saveDraft()
    {
        $this->validate();
        $this->saving = true;

        try {
            ContentCalendarItem::create([
                'client_id' => $this->client_id,
                'title' => $this->title,
                'content_type' => 'social',
                'platform' => $this->platform,
                'content_text' => $this->content_text,
                'hashtags' => $this->hashtags,
                'campaign_tag' => $this->campaign_tag,
                'scheduled_for' => $this->scheduled_for,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'meta' => [
                    'ai_prompt' => $this->ai_prompt,
                    'ai_tone' => $this->ai_tone,
                ],
            ]);

            session()->flash('success', 'Draft saved successfully!');
            return redirect()->route('admin.social.posts');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save draft: ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function submitForApproval()
    {
        $this->validate();
        $this->saving = true;

        try {
            $post = ContentCalendarItem::create([
                'client_id' => $this->client_id,
                'title' => $this->title,
                'content_type' => 'social',
                'platform' => $this->platform,
                'content_text' => $this->content_text,
                'hashtags' => $this->hashtags,
                'campaign_tag' => $this->campaign_tag,
                'scheduled_for' => $this->scheduled_for,
                'status' => 'pending_approval',
                'created_by' => auth()->id(),
                'meta' => [
                    'ai_prompt' => $this->ai_prompt,
                    'ai_tone' => $this->ai_tone,
                ],
            ]);

            // TODO: Send notification to client

            session()->flash('success', 'Post submitted for client approval!');
            return redirect()->route('admin.social.posts');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit for approval: ' . $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.social.post-creator', [
            'clients' => Client::active()->orderBy('company_name')->get(),
            'platforms' => [
                'facebook' => 'Facebook',
                'instagram' => 'Instagram',
                'linkedin' => 'LinkedIn',
                'x' => 'X (Twitter)',
                'tiktok' => 'TikTok',
                'pinterest' => 'Pinterest',
            ],
            'tones' => [
                'professional' => 'Professional',
                'casual' => 'Casual',
                'friendly' => 'Friendly',
                'humorous' => 'Humorous',
                'inspirational' => 'Inspirational',
                'educational' => 'Educational',
                'promotional' => 'Promotional',
            ],
        ]);
    }
}
