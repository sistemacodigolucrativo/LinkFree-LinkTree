<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

/** Public metadata only. Never use private account fields in cards or markup. */
final class PublicSeo
{
    public const CARD_VERSION = 'linkfree-horizontal-v4';

    public static function plain(?string $text): string
    {
        $text = strip_tags(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    public static function description(?string $text, string $fallback, int $limit = 170): string
    {
        $text = self::plain($text);
        return mb_strimwidth($text !== '' ? $text : $fallback, 0, $limit, '…', 'UTF-8');
    }

    public static function robots(bool $noIndex = false): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', strtolower(
            (string) config('advanced-config.robots', 'index,follow')
        )))));
        if (!$parts) $parts = ['index', 'follow'];
        if ($noIndex || in_array('none', $parts, true) || in_array('noindex', $parts, true)) {
            $parts = array_values(array_diff($parts, ['index', 'all']));
            if (!in_array('none', $parts, true)) {
                $parts = array_values(array_diff($parts, ['noindex']));
                array_unshift($parts, 'noindex');
            }
            if (!in_array('none', $parts, true) && !in_array('nofollow', $parts, true) && !in_array('follow', $parts, true)) $parts[] = 'follow';
        } elseif (!array_filter($parts, fn ($part) => str_starts_with($part, 'max-image-preview:'))) {
            $parts[] = 'max-image-preview:large';
        }
        return implode(', ', array_unique($parts));
    }

    public static function globallyNoIndex(): bool
    {
        $parts = array_map('trim', explode(',', self::robots()));
        return in_array('noindex', $parts, true) || in_array('none', $parts, true);
    }

    // Match the existing boolean UserData contract without loading private data into the response.
    public static function profileNoIndex(User $user): bool
    {
        $data = json_decode((string) $user->image, true);
        return is_array($data) && ($data['search_noindex'] ?? null) === true;
    }

    public static function customDomain(Request $request): ?array
    {
        foreach ((array) config('advanced-config.custom_domains', []) as $domain) {
            if (is_array($domain) && strtolower((string) ($domain['domain'] ?? '')) === strtolower($request->getHost())) return $domain;
        }
        return null;
    }

    public static function customMatches(array $domain, User $user): bool
    {
        // The home route resolves the name when present and otherwise the ID.
        if (isset($domain['name'], $domain['id'])) {
            return (string) $domain['name'] === (string) $user->id;
        }
        if (isset($domain['name'])) return (string) $domain['name'] === (string) $user->littlelink_name;
        return isset($domain['id']) && (string) $domain['id'] === (string) $user->id;
    }

    public static function profileUrl(Request $request, User $user): string
    {
        $custom = self::customDomain($request);
        if (($custom && self::customMatches($custom, $user)) ||
            (!$custom && (string) env('HOME_URL', '') === (string) $user->littlelink_name)) {
            return url('/');
        }
        return route('littlelink', ['littlelink' => $user->littlelink_name]);
    }

    public static function canonical(string $default): string
    {
        $override = trim((string) config('advanced-config.canonical_url', ''));
        if (env('CUSTOM_META_TAGS') === 'true' && filter_var($override, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($override, PHP_URL_SCHEME)), ['http', 'https'], true)
            && parse_url($override, PHP_URL_USER) === null && parse_url($override, PHP_URL_PASS) === null) {
            return $override;
        }
        return $default;
    }

    // Local, bounded raster files only. Never fetch remote avatars, or follow a symlink outside the public directory.
    private static function raster(string $relative, string $directory): ?string
    {
        $root = realpath(base_path($directory));
        $file = realpath(base_path($relative));
        if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)
            || !is_file($file) || !is_readable($file) || filesize($file) > 10 * 1024 * 1024) return null;
        $details = @getimagesize($file);
        if (!$details || $details[0] < 1 || $details[1] < 1 || $details[0] * $details[1] > 16000000
            || !in_array($details[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) return null;
        return $file;
    }

    public static function avatar(?int $id = null): ?string
    {
        if ($id !== null && is_dir(base_path('assets/img'))) {
            $avatar = self::raster(findAvatar($id), 'assets/img');
            if ($avatar !== null) return $avatar;
        }
        return self::raster('assets/linkstack/images/' . findFile('avatar'), 'assets/linkstack/images')
            ?? self::raster('assets/linkstack/images/social-brand.png', 'assets/linkstack/images');
    }

    public static function version(string $name, string $description, ?string $avatar, string $updated = ''): string
    {
        $stat = $avatar ? @stat($avatar) : false;
        $fileVersion = $stat ? basename($avatar) . '|' . $stat['mtime'] . '|' . $stat['size'] : 'brand';
        return substr(hash('sha256', self::CARD_VERSION . '|' . $name . '|' . $description . '|' . $updated . '|' . $fileVersion), 0, 16);
    }

    public static function homeTitle(): string
    {
        $title = self::plain(config('app.name', 'LinkFree')) . ' | Crie sua página de links gratuita';
        if (env('CUSTOM_META_TAGS') === 'true' && trim((string) config('advanced-config.title', '')) !== '') return self::plain(config('advanced-config.title'));
        return $title;
    }

    public static function homeDescription(): string
    {
        $configured = env('CUSTOM_META_TAGS') === 'true' ? self::plain(config('advanced-config.description')) : '';
        return self::description($configured ?: PublicSiteContent::text('hero_description'),
            'Crie sua página de links grátis e reúna seus contatos em um único endereço.', 175);
    }

    public static function locale(): string
    {
        $locale = str_replace('-', '_', app()->getLocale());
        return $locale === 'pt' || strtolower($locale) === 'pt_br' ? 'pt_BR' : $locale;
    }
}
