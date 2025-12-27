<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class SocialMediaAIService
{
    public function __construct(
        protected AIProviderManager $aiManager
    ) {}

    /**
     * Generate social media post content using AI
     *
     * @param  string  $prompt  User's content idea or topic
     * @param  string  $platform  Target platform (facebook, instagram, linkedin, x, tiktok)
     * @param  array  $options  Additional options (tone, length, hashtags, emoji usage, etc.)
     */
    public function generatePost(string $prompt, string $platform, array $options = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($platform, $options);
        $userPrompt = $this->buildUserPrompt($prompt, $options);

        try {
            $response = $this->aiManager->generateText(
                prompt: $userPrompt,
                systemPrompt: $systemPrompt,
                temperature: $options['creativity'] ?? 0.7,
                maxTokens: $this->getMaxTokens($platform)
            );

            return $this->parseAIResponse($response, $platform);
        } catch (\Exception $e) {
            Log::error('AI social media generation failed', [
                'prompt' => $prompt,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate multiple variations of a post
     *
     * @param  int  $count  Number of variations to generate
     */
    public function generateVariations(string $prompt, string $platform, int $count = 3, array $options = []): array
    {
        $variations = [];

        for ($i = 0; $i < $count; $i++) {
            // Vary temperature for different variations
            $options['creativity'] = 0.6 + ($i * 0.15);
            $variations[] = $this->generatePost($prompt, $platform, $options);
        }

        return $variations;
    }

    /**
     * Adapt existing content for different platform
     *
     * @param  string  $content  Original content
     * @param  string  $fromPlatform  Original platform
     * @param  string  $toPlatform  Target platform
     */
    public function adaptForPlatform(string $content, string $fromPlatform, string $toPlatform): array
    {
        $systemPrompt = "You are a social media expert. Adapt the following {$fromPlatform} post for {$toPlatform}.

Maintain the core message but adjust:
- Tone and style for {$toPlatform} audience
- Character count for platform limits
- Hashtag usage based on platform norms
- Emoji usage appropriate for the platform

Return a JSON response with:
{
    \"content\": \"adapted post text\",
    \"hashtags\": [\"hashtag1\", \"hashtag2\"],
    \"caption\": \"optional caption for image posts\"
}";

        $userPrompt = "Original {$fromPlatform} post:\n\n{$content}\n\nAdapt this for {$toPlatform}.";

        $response = $this->aiManager->generateText(
            prompt: $userPrompt,
            systemPrompt: $systemPrompt,
            temperature: 0.7
        );

        return $this->parseAIResponse($response, $toPlatform);
    }

    /**
     * Generate hashtags for content
     *
     * @param  string  $content  Post content
     * @param  string  $platform  Target platform
     * @param  int  $count  Number of hashtags to generate
     */
    public function generateHashtags(string $content, string $platform, int $count = 5): array
    {
        $hashtagLimits = [
            'instagram' => '20-30 hashtags recommended',
            'x' => '1-2 hashtags recommended',
            'linkedin' => '3-5 hashtags recommended',
            'facebook' => '1-3 hashtags recommended',
            'tiktok' => '3-5 hashtags recommended',
        ];

        $platformGuidance = $hashtagLimits[$platform] ?? '3-5 hashtags recommended';

        $systemPrompt = "You are a social media hashtag expert. Generate relevant, trending hashtags.

Platform: {$platform}
Guidance: {$platformGuidance}

Return a JSON array of hashtags (without # symbol):
[\"hashtag1\", \"hashtag2\", \"hashtag3\"]

Mix of:
- Broad hashtags for reach
- Niche hashtags for engagement
- Trending hashtags when relevant";

        $userPrompt = "Generate {$count} effective hashtags for this {$platform} post:\n\n{$content}";

        $response = $this->aiManager->generateText(
            prompt: $userPrompt,
            systemPrompt: $systemPrompt,
            temperature: 0.6
        );

        // Parse JSON response
        $cleaned = $this->cleanJsonResponse($response);
        $hashtags = json_decode($cleaned, true);

        return is_array($hashtags) ? $hashtags : [];
    }

    /**
     * Analyze content sentiment and suggest improvements
     */
    public function analyzeAndImprove(string $content): array
    {
        $systemPrompt = 'You are a social media content analyst. Analyze the post and provide actionable feedback.

Return JSON:
{
    "sentiment": "positive|neutral|negative",
    "engagement_score": 1-10,
    "readability_score": 1-10,
    "strengths": ["strength1", "strength2"],
    "improvements": ["suggestion1", "suggestion2"],
    "improved_version": "optional improved text"
}';

        $userPrompt = "Analyze this social media post:\n\n{$content}";

        $response = $this->aiManager->generateText(
            prompt: $userPrompt,
            systemPrompt: $systemPrompt,
            temperature: 0.5
        );

        $cleaned = $this->cleanJsonResponse($response);

        return json_decode($cleaned, true) ?? [];
    }

    /**
     * Generate image captions/descriptions
     *
     * @param  string  $imageContext  Description of the image
     */
    public function generateImageCaption(string $imageContext, string $platform): string
    {
        $systemPrompt = "You are a social media caption writer. Create an engaging caption for an image post.

Platform: {$platform}
- Make it platform-appropriate
- Include relevant emojis
- Create urgency or curiosity

Return just the caption text.";

        $userPrompt = "Image description: {$imageContext}\n\nWrite an engaging caption.";

        return $this->aiManager->generateText(
            prompt: $userPrompt,
            systemPrompt: $systemPrompt,
            temperature: 0.8
        );
    }

    /**
     * Suggest best posting times
     */
    public function suggestPostingTimes(string $platform, string $targetAudience = 'general', string $timezone = 'America/New_York'): array
    {
        $systemPrompt = "You are a social media strategist. Suggest optimal posting times.

Consider:
- Platform algorithms
- Target audience behavior
- Time zone: {$timezone}
- Engagement patterns

Return JSON with 3-5 best times:
[
    {\"day\": \"Monday\", \"time\": \"09:00\", \"reason\": \"High engagement during morning commute\"},
    ...
]";

        $userPrompt = "Platform: {$platform}\nTarget Audience: {$targetAudience}\n\nWhat are the best times to post?";

        $response = $this->aiManager->generateText(
            prompt: $userPrompt,
            systemPrompt: $systemPrompt,
            temperature: 0.4
        );

        $cleaned = $this->cleanJsonResponse($response);

        return json_decode($cleaned, true) ?? [];
    }

    /**
     * Build system prompt based on platform and options
     */
    protected function buildSystemPrompt(string $platform, array $options): string
    {
        $platformGuides = [
            'facebook' => 'Facebook: Conversational, community-focused. 40-80 words optimal. Emojis encouraged. Ask questions to drive engagement.',
            'instagram' => 'Instagram: Visual-first, trendy, authentic. 138-150 characters in caption. Heavy emoji usage. Story-driven.',
            'linkedin' => 'LinkedIn: Professional, thought leadership. 150-300 words. Minimal emojis. Industry insights and value.',
            'x' => 'X/Twitter: Concise, timely, witty. Max 280 characters. 1-2 hashtags. News and trending topics.',
            'tiktok' => 'TikTok: Casual, entertaining, trendy. Short and punchy. Heavy emoji and slang usage. Call-to-action focused.',
        ];

        $platformGuide = $platformGuides[$platform] ?? 'General social media platform.';

        $tone = $options['tone'] ?? 'professional yet engaging';
        $includeHashtags = $options['include_hashtags'] ?? true;
        $includeEmoji = $options['include_emoji'] ?? true;
        $includeCallToAction = $options['include_cta'] ?? true;

        return "You are an expert social media content creator specializing in {$platform}.

Platform Guidelines: {$platformGuide}

Tone: {$tone}
Hashtags: ".($includeHashtags ? 'Include relevant hashtags' : 'No hashtags').'
Emojis: '.($includeEmoji ? 'Use appropriately' : 'Avoid emojis').'
Call-to-Action: '.($includeCallToAction ? 'Include engaging CTA' : 'No CTA needed').'

Return JSON format:
{
    "content": "main post text",
    "hashtags": ["hashtag1", "hashtag2"],
    "caption": "optional image caption",
    "cta": "call to action text"
}

Make the content engaging, on-brand, and optimized for maximum engagement.';
    }

    /**
     * Build user prompt with context
     */
    protected function buildUserPrompt(string $prompt, array $options): string
    {
        $context = '';

        if (isset($options['brand_voice'])) {
            $context .= "Brand Voice: {$options['brand_voice']}\n";
        }

        if (isset($options['target_audience'])) {
            $context .= "Target Audience: {$options['target_audience']}\n";
        }

        if (isset($options['campaign'])) {
            $context .= "Campaign: {$options['campaign']}\n";
        }

        if (isset($options['keywords'])) {
            $keywords = is_array($options['keywords']) ? implode(', ', $options['keywords']) : $options['keywords'];
            $context .= "Keywords to include: {$keywords}\n";
        }

        return "{$context}\nContent idea: {$prompt}";
    }

    /**
     * Parse AI response
     */
    protected function parseAIResponse(string $response, string $platform): array
    {
        // Try to extract JSON from response
        $cleaned = $this->cleanJsonResponse($response);
        $data = json_decode($cleaned, true);

        if (! $data) {
            // Fallback if JSON parsing fails
            return [
                'content' => $response,
                'hashtags' => [],
                'caption' => '',
                'cta' => '',
            ];
        }

        return [
            'content' => $data['content'] ?? $response,
            'hashtags' => $data['hashtags'] ?? [],
            'caption' => $data['caption'] ?? '',
            'cta' => $data['cta'] ?? '',
            'meta' => $data['meta'] ?? [],
        ];
    }

    /**
     * Clean JSON response from AI (remove markdown code blocks, etc.)
     */
    protected function cleanJsonResponse(string $response): string
    {
        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);

        // Trim whitespace
        $response = trim($response);

        return $response;
    }

    /**
     * Get max tokens based on platform
     */
    protected function getMaxTokens(string $platform): int
    {
        return match ($platform) {
            'x' => 100,        // Short tweets
            'instagram' => 300, // Short captions
            'facebook' => 400,  // Medium posts
            'linkedin' => 600,  // Longer posts
            'tiktok' => 200,    // Short captions
            default => 400
        };
    }
}
