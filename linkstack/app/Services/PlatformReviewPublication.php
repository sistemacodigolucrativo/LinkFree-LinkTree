<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlatformReviewPublication
{
    public static function published(int $limit = 6)
    {
        // An older production installation must not break the public home
        // while the additive migration is waiting for its guarded deploy.
        if (!Schema::hasTable('platform_reviews')) {
            return collect();
        }

        return DB::table('platform_reviews')
            ->where('status', 'approved')
            ->where('publication_consent', true)
            ->whereNotNull('consented_at')
            ->select('rating', 'testimonial', 'created_at')
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 24)))
            ->get();
    }

    public static function contributionUrl(): ?string
    {
        // Intentionally unset by default. No link to LinkStack or a payment
        // destination is allowed without an operator-approved configuration.
        $url = trim((string) config('advanced-config.voluntary_contribution_url', ''));
        if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }
}
