<?php

namespace App\Services\AI;

use App\Models\PromptTemplate;
use App\Models\PromptTemplateVersion;

class PromptTemplateService
{
    /**
     * Resolve an active template version for a key.
     */
    public function resolveActiveVersion(string $key): ?PromptTemplateVersion
    {
        $tpl = PromptTemplate::query()->where('key', $key)->where('status', 'active')->first();
        if (! $tpl) {
            return null;
        }

        return PromptTemplateVersion::query()
            ->where('prompt_template_id', $tpl->id)
            ->where('status', 'active')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Render a template by replacing {{var}} placeholders.
     */
    public function render(string $prompt, array $vars = []): string
    {
        $out = $prompt;
        foreach ($vars as $k => $v) {
            $key = (string) $k;
            $val = is_scalar($v) || $v === null ? (string) ($v ?? '') : json_encode($v, JSON_UNESCAPED_SLASHES);
            $out = str_replace('{{'.$key.'}}', $val, $out);
        }

        return $out;
    }

    /**
     * Get rendered system prompt for a key, with fallback default text.
     */
    public function systemPrompt(string $key, array $vars = [], ?string $default = null): string
    {
        $v = $this->resolveActiveVersion($key);
        if (! $v) {
            return $default ?? '';
        }

        return $this->render((string) $v->system_prompt, $vars);
    }
}
