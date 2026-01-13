<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorAnalysis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'created_by',
        'competitor_name',
        'competitor_url',
        'competitor_industry',
        'status',
        'company_overview',
        'products_services',
        'market_position',
        'strengths',
        'weaknesses',
        'opportunities',
        'threats',
        'pricing_strategy',
        'marketing_channels',
        'target_audience',
        'technology_stack',
        'online_presence',
        'content_strategy',
        'customer_reviews',
        'gaps_limitations',
        'competitive_advantages',
        'recommendations',
        'sources',
        'raw_response',
        'analysis_summary',
        'confidence_score',
        'processing_time_ms',
        'analyzed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_overview' => 'array',
            'products_services' => 'array',
            'market_position' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'opportunities' => 'array',
            'threats' => 'array',
            'pricing_strategy' => 'array',
            'marketing_channels' => 'array',
            'target_audience' => 'array',
            'technology_stack' => 'array',
            'online_presence' => 'array',
            'content_strategy' => 'array',
            'customer_reviews' => 'array',
            'gaps_limitations' => 'array',
            'competitive_advantages' => 'array',
            'recommendations' => 'array',
            'sources' => 'array',
            'raw_response' => 'array',
            'confidence_score' => 'decimal:2',
            'analyzed_at' => 'datetime',
        ];
    }

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the client that owns the analysis.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the analysis.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for completed analyses.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending analyses.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Check if the analysis is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the analysis failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the analysis is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Get the SWOT analysis as a structured array.
     *
     * @return array<string, array|null>
     */
    public function getSwotAnalysis(): array
    {
        return [
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'opportunities' => $this->opportunities,
            'threats' => $this->threats,
        ];
    }

    /**
     * Get a summary of key findings.
     *
     * @return array<string, mixed>
     */
    public function getKeyFindings(): array
    {
        return [
            'gaps_limitations' => $this->gaps_limitations,
            'competitive_advantages' => $this->competitive_advantages,
            'recommendations' => $this->recommendations,
        ];
    }

    /**
     * Get marketing intelligence data.
     *
     * @return array<string, mixed>
     */
    public function getMarketingIntelligence(): array
    {
        return [
            'pricing_strategy' => $this->pricing_strategy,
            'marketing_channels' => $this->marketing_channels,
            'target_audience' => $this->target_audience,
            'content_strategy' => $this->content_strategy,
            'online_presence' => $this->online_presence,
        ];
    }
}
