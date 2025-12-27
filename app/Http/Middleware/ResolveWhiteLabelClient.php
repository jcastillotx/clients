<?php

namespace App\Http\Middleware;

use App\Models\WhiteLabelConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveWhiteLabelClient
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tests or minimal environments may not have this table migrated yet.
        if (!Schema::hasTable('white_label_configs')) {
            return $next($request);
        }

        $host = strtolower((string) $request->getHost());

        $cfg = WhiteLabelConfig::query()
            ->where('is_active', true)
            ->whereNotNull('custom_domain')
            ->whereRaw('LOWER(custom_domain) = ?', [$host])
            ->first();

        if ($cfg) {
            // Store for request lifecycle (used by views/components).
            $request->attributes->set('white_label_client_id', (int) $cfg->client_id);
            $request->attributes->set('white_label_config_id', (int) $cfg->id);
        }

        return $next($request);
    }
}

