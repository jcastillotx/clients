<?php

namespace App\Services\SEO;

use App\Models\Client;
use App\Models\LocalRanking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Local SEO Service for Map Pack Tracking
 *
 * Provides local search ranking data including:
 * - Google Maps / Local Pack rankings from multiple geo-points (grid-based)
 * - Google Business Profile data
 * - Local keyword tracking
 * - Competitor analysis in map pack
 *
 * Uses DataForSEO API for local SERP data
 * @see https://docs.dataforseo.com/v3/serp/google/maps/
 */
class LocalSeoService
{
    protected string $endpoint = 'https://api.dataforseo.com/v3';

    protected ?string $login;

    protected ?string $password;

    protected bool $enabled;

    public function __construct()
    {
        $this->login = config('seo.low_cost.dataforseo.login') ?: app('settings')->get('api.seo.dataforseo.login');
        $this->password = config('seo.low_cost.dataforseo.password') ?: app('settings')->get('api.seo.dataforseo.password');
        $this->enabled = (bool) (config('seo.low_cost.dataforseo.enabled') ?? app('settings')->get('api.seo.dataforseo.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->login) && !empty($this->password);
    }

    /**
     * Get Map Pack (Local Pack) rankings for a keyword
     *
     * @param string $keyword Search keyword
     * @param string $location Location name (e.g., "Austin,Texas,United States")
     * @param string $language Language code
     * @return array
     */
    public function getMapPackResults(string $keyword, string $location, string $language = 'en'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'localseo:mappack:' . md5($keyword . $location . $language);

        return Cache::remember($cacheKey, 3600, function () use ($keyword, $location, $language) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(60)
                    ->post("{$this->endpoint}/serp/google/maps/live/advanced", [
                        [
                            'keyword' => $keyword,
                            'location_name' => $location,
                            'language_name' => $language,
                            'depth' => 20,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        $results = $data['tasks'][0]['result'][0] ?? [];
                        $items = $results['items'] ?? [];

                        // Parse map pack results
                        $mapPackResults = [];
                        foreach ($items as $index => $item) {
                            if (($item['type'] ?? '') === 'maps_search') {
                                $mapPackResults[] = [
                                    'position' => $index + 1,
                                    'title' => $item['title'] ?? '',
                                    'rating' => $item['rating']['value'] ?? null,
                                    'reviews_count' => $item['rating']['votes_count'] ?? 0,
                                    'address' => $item['address'] ?? '',
                                    'phone' => $item['phone'] ?? '',
                                    'website' => $item['url'] ?? '',
                                    'category' => $item['category'] ?? '',
                                    'place_id' => $item['place_id'] ?? '',
                                    'cid' => $item['cid'] ?? '',
                                    'latitude' => $item['latitude'] ?? null,
                                    'longitude' => $item['longitude'] ?? null,
                                    'is_claimed' => $item['is_claimed'] ?? false,
                                    'work_hours' => $item['work_hours'] ?? null,
                                ];
                            }
                        }

                        return [
                            'success' => true,
                            'keyword' => $keyword,
                            'location' => $location,
                            'results' => $mapPackResults,
                            'total_count' => count($mapPackResults),
                            'cost' => $data['cost'] ?? null,
                        ];
                    }

                    return ['success' => false, 'error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
                }

                return ['success' => false, 'error' => 'API request failed: ' . $response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('LocalSEO map pack error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Grid-based Map Pack tracking
     * Check rankings from multiple geo-points around a central location
     *
     * @param string $keyword Search keyword
     * @param float $centerLat Center latitude
     * @param float $centerLng Center longitude
     * @param int $gridSize Grid size (3x3, 5x5, 7x7)
     * @param float $radiusMiles Radius in miles for the grid
     * @param string $businessName Business name to find in results
     * @return array
     */
    public function getGridRankings(
        string $keyword,
        float $centerLat,
        float $centerLng,
        int $gridSize = 5,
        float $radiusMiles = 5,
        string $businessName = ''
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'localseo:grid:' . md5($keyword . $centerLat . $centerLng . $gridSize . $radiusMiles . $businessName);

        return Cache::remember($cacheKey, 3600, function () use ($keyword, $centerLat, $centerLng, $gridSize, $radiusMiles, $businessName) {
            // Generate grid points
            $gridPoints = $this->generateGridPoints($centerLat, $centerLng, $gridSize, $radiusMiles);
            $results = [];
            $businessRankings = [];

            foreach ($gridPoints as $point) {
                try {
                    $response = Http::withBasicAuth($this->login, $this->password)
                        ->timeout(60)
                        ->post("{$this->endpoint}/serp/google/maps/live/advanced", [
                            [
                                'keyword' => $keyword,
                                'location_coordinate' => "{$point['lat']},{$point['lng']}",
                                'language_name' => 'English',
                                'depth' => 20,
                            ],
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['status_code'] ?? 0) === 20000) {
                            $items = $data['tasks'][0]['result'][0]['items'] ?? [];

                            $pointResults = [];
                            $businessPosition = null;

                            foreach ($items as $index => $item) {
                                if (($item['type'] ?? '') === 'maps_search') {
                                    $position = $index + 1;
                                    $title = $item['title'] ?? '';

                                    $pointResults[] = [
                                        'position' => $position,
                                        'title' => $title,
                                        'rating' => $item['rating']['value'] ?? null,
                                    ];

                                    // Check if this is the target business
                                    if (!empty($businessName) && stripos($title, $businessName) !== false) {
                                        $businessPosition = $position;
                                    }
                                }
                            }

                            $results[] = [
                                'lat' => $point['lat'],
                                'lng' => $point['lng'],
                                'row' => $point['row'],
                                'col' => $point['col'],
                                'business_position' => $businessPosition,
                                'top_results' => array_slice($pointResults, 0, 3),
                            ];

                            if ($businessPosition !== null) {
                                $businessRankings[] = $businessPosition;
                            }
                        }
                    }

                    // Rate limiting - small delay between requests
                    usleep(100000); // 100ms delay

                } catch (\Throwable $e) {
                    Log::warning('Grid point error', ['point' => $point, 'error' => $e->getMessage()]);
                    $results[] = [
                        'lat' => $point['lat'],
                        'lng' => $point['lng'],
                        'row' => $point['row'],
                        'col' => $point['col'],
                        'business_position' => null,
                        'error' => true,
                    ];
                }
            }

            // Calculate statistics
            $avgPosition = count($businessRankings) > 0 ? round(array_sum($businessRankings) / count($businessRankings), 1) : null;
            $top3Count = count(array_filter($businessRankings, fn($p) => $p <= 3));
            $notFoundCount = $gridSize * $gridSize - count($businessRankings);

            return [
                'success' => true,
                'keyword' => $keyword,
                'business_name' => $businessName,
                'center' => ['lat' => $centerLat, 'lng' => $centerLng],
                'grid_size' => $gridSize,
                'radius_miles' => $radiusMiles,
                'grid_results' => $results,
                'stats' => [
                    'average_position' => $avgPosition,
                    'top_3_count' => $top3Count,
                    'found_count' => count($businessRankings),
                    'not_found_count' => $notFoundCount,
                    'visibility_score' => $gridSize * $gridSize > 0
                        ? round(($top3Count / ($gridSize * $gridSize)) * 100, 1)
                        : 0,
                ],
            ];
        });
    }

    /**
     * Generate grid points around a center coordinate
     */
    protected function generateGridPoints(float $centerLat, float $centerLng, int $gridSize, float $radiusMiles): array
    {
        $points = [];
        $halfGrid = floor($gridSize / 2);

        // Convert miles to degrees (approximate)
        $latDegPerMile = 1 / 69.0;
        $lngDegPerMile = 1 / (69.0 * cos(deg2rad($centerLat)));

        $stepMiles = ($radiusMiles * 2) / ($gridSize - 1);

        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $latOffset = ($row - $halfGrid) * $stepMiles * $latDegPerMile;
                $lngOffset = ($col - $halfGrid) * $stepMiles * $lngDegPerMile;

                $points[] = [
                    'lat' => round($centerLat + $latOffset, 6),
                    'lng' => round($centerLng + $lngOffset, 6),
                    'row' => $row,
                    'col' => $col,
                ];
            }
        }

        return $points;
    }

    /**
     * Get Google Business Profile info
     */
    public function getBusinessProfileInfo(string $placeId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'localseo:gbp:' . md5($placeId);

        return Cache::remember($cacheKey, 3600, function () use ($placeId) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(30)
                    ->post("{$this->endpoint}/business_data/google/my_business_info/task_post", [
                        [
                            'place_id' => $placeId,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        return [
                            'success' => true,
                            'data' => $data['tasks'][0]['result'][0] ?? [],
                            'cost' => $data['cost'] ?? null,
                        ];
                    }
                    return ['success' => false, 'error' => $data['status_message'] ?? 'Unknown error'];
                }
                return ['success' => false, 'error' => 'API request failed'];
            } catch (\Throwable $e) {
                Log::error('GBP info error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Get reviews for a business
     */
    public function getBusinessReviews(string $placeId, int $limit = 100): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'localseo:reviews:' . md5($placeId . $limit);

        return Cache::remember($cacheKey, 1800, function () use ($placeId, $limit) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(60)
                    ->post("{$this->endpoint}/business_data/google/reviews/task_post", [
                        [
                            'place_id' => $placeId,
                            'depth' => $limit,
                            'sort_by' => 'newest',
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        return [
                            'success' => true,
                            'task_id' => $data['tasks'][0]['id'] ?? null,
                            'cost' => $data['cost'] ?? null,
                        ];
                    }
                    return ['success' => false, 'error' => $data['status_message'] ?? 'Unknown error'];
                }
                return ['success' => false, 'error' => 'API request failed'];
            } catch (\Throwable $e) {
                Log::error('Reviews error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Search for local competitors for a keyword
     */
    public function findLocalCompetitors(string $keyword, string $location, string $targetBusinessName = ''): array
    {
        $mapPackResults = $this->getMapPackResults($keyword, $location);

        if (!$mapPackResults['success']) {
            return $mapPackResults;
        }

        $competitors = [];
        $targetPosition = null;

        foreach ($mapPackResults['results'] as $result) {
            if (!empty($targetBusinessName) && stripos($result['title'], $targetBusinessName) !== false) {
                $targetPosition = $result['position'];
                continue;
            }

            $competitors[] = [
                'name' => $result['title'],
                'position' => $result['position'],
                'rating' => $result['rating'],
                'reviews_count' => $result['reviews_count'],
                'website' => $result['website'],
                'phone' => $result['phone'],
            ];
        }

        return [
            'success' => true,
            'keyword' => $keyword,
            'location' => $location,
            'target_position' => $targetPosition,
            'competitors' => $competitors,
        ];
    }

    /**
     * Save grid ranking results to database
     */
    public function saveGridRanking(Client $client, string $keyword, array $gridResults): LocalRanking
    {
        return LocalRanking::updateOrCreate(
            [
                'client_id' => $client->id,
                'keyword' => $keyword,
                'tracked_date' => now()->toDateString(),
            ],
            [
                'center_lat' => $gridResults['center']['lat'] ?? null,
                'center_lng' => $gridResults['center']['lng'] ?? null,
                'grid_size' => $gridResults['grid_size'] ?? 5,
                'radius_miles' => $gridResults['radius_miles'] ?? 5,
                'grid_data' => $gridResults['grid_results'] ?? [],
                'average_position' => $gridResults['stats']['average_position'] ?? null,
                'top_3_count' => $gridResults['stats']['top_3_count'] ?? 0,
                'visibility_score' => $gridResults['stats']['visibility_score'] ?? 0,
                'tracked_at' => now(),
            ]
        );
    }

    /**
     * Get location suggestions for autocomplete
     */
    public function getLocationSuggestions(string $query): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Not configured', 'data' => []];
        }

        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout(10)
                ->get("{$this->endpoint}/serp/google/locations", [
                    'location_name' => $query,
                    'limit' => 10,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $locations = [];

                foreach ($data['tasks'][0]['result'] ?? [] as $loc) {
                    $locations[] = [
                        'name' => $loc['location_name'] ?? '',
                        'code' => $loc['location_code'] ?? '',
                        'type' => $loc['location_type'] ?? '',
                    ];
                }

                return ['success' => true, 'locations' => $locations];
            }

            return ['success' => false, 'error' => 'Request failed'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'LocalSEO/DataForSEO not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout(10)
                ->get("{$this->endpoint}/serp/google/maps/locations");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
