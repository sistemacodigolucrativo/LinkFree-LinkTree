<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PublicSeo;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    private const PAGE_SIZE = 1000;

    private function profiles()
    {
        return User::query()->whereNotNull('littlelink_name')->where('littlelink_name', '<>', '')
            ->where(function ($query) {
                $query->whereNull('block')->orWhere('block', '<>', 'yes');
            });
    }

    private function selectedProfile(Request $request): ?User
    {
        $domain = PublicSeo::customDomain($request);
        $query = $this->profiles()->select('id', 'littlelink_name', 'image');
        if ($domain !== null) {
            if (isset($domain['id'])) return $query->where('id', $domain['name'] ?? $domain['id'])->first();
            if (isset($domain['name'])) return $query->where('littlelink_name', $domain['name'])->first();
            return null;
        }
        $home = (string) env('HOME_URL', '');
        return $home !== '' ? $query->where('littlelink_name', $home)->first() : null;
    }

    private function homeEntries(Request $request): array
    {
        if (PublicSeo::globallyNoIndex()) return [];
        $entries = [];
        $domain = PublicSeo::customDomain($request);
        $hasProfile = $domain !== null || (string) env('HOME_URL', '') !== '';
        if ($hasProfile) {
            $user = $this->selectedProfile($request);
            if ($user && !PublicSeo::profileNoIndex($user) && PublicSeo::canonical(url('/')) === url('/')) $entries[] = url('/');
            if ($domain !== null) return $entries;
        }
        $disabled = config('advanced-config.disable_home_page');
        if (!in_array($disabled, ['true', 'redirect', true], true)) {
            $home = $hasProfile ? url(config('advanced-config.custom_home_url', '/home')) : url('/');
            if (PublicSeo::canonical($home) === $home) $entries[] = $home;
            // Mesma política de canonical/noindex aplicada à home institucional.
            foreach ([route('public.resources'), route('public.roadmap')] as $publicUrl) {
                if (PublicSeo::canonical($publicUrl) === $publicUrl) $entries[] = $publicUrl;
            }
        }
        return array_values(array_unique($entries));
    }

    public function robots(Request $request)
    {
        $lines = ['User-agent: *', 'Allow: /@', 'Allow: /social-preview/',
            'Disallow: /admin/', 'Disallow: /studio/', 'Disallow: /dashboard',
            'Disallow: /login', 'Disallow: /register', 'Disallow: /password/',
            'Disallow: /export-', 'Disallow: /import-data', 'Disallow: /going/',
            'Disallow: /info/', 'Disallow: /demo-page', 'Disallow: /theme/',
            'Sitemap: ' . route('seo.sitemap')];
        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sitemap(Request $request)
    {
        if (PublicSeo::globallyNoIndex() || PublicSeo::customDomain($request) !== null) return $this->urlset($this->homeEntries($request));
        // Count candidate rows without exposing private values. Opt-outs are filtered
        // from each bounded page before emitting URLs. Empty pages are valid XML.
        $pages = (int) ceil($this->profiles()->count() / self::PAGE_SIZE);
        $items = '<sitemap><loc>' . $this->escape(route('seo.sitemap.home')) . '</loc></sitemap>';
        for ($page = 1; $page <= $pages; $page++) {
            $items .= '<sitemap><loc>' . $this->escape(route('seo.sitemap.profiles', ['page' => $page])) . '</loc></sitemap>';
        }
        return $this->xml('sitemapindex', $items);
    }

    public function homeSitemap(Request $request)
    {
        return $this->urlset($this->homeEntries($request));
    }

    public function profilesSitemap(Request $request, int $page)
    {
        if ($page < 1) abort(404);
        if (PublicSeo::globallyNoIndex() || PublicSeo::customDomain($request) !== null) return $this->urlset([]);
        $query = $this->profiles();
        $pages = (int) ceil($query->count() / self::PAGE_SIZE);
        if ($page > max(1, $pages)) abort(404);
        $users = $query->select('id', 'littlelink_name', 'image', 'updated_at')
            ->orderBy('id')->offset(($page - 1) * self::PAGE_SIZE)->limit(self::PAGE_SIZE)->get();
        $entries = [];
        foreach ($users as $user) {
            if (PublicSeo::profileNoIndex($user) || (string) env('HOME_URL', '') === $user->littlelink_name) continue;
            $url = PublicSeo::profileUrl($request, $user);
            if (PublicSeo::canonical($url) !== $url) continue;
            $entries[] = ['url' => $url, 'lastmod' => $user->updated_at ? $user->updated_at->toDateString() : null];
        }
        return $this->urlset($entries);
    }

    private function urlset(array $entries)
    {
        $items = '';
        foreach ($entries as $entry) {
            $items .= '<url><loc>' . $this->escape(is_array($entry) ? $entry['url'] : $entry) . '</loc>';
            if (is_array($entry) && !empty($entry['lastmod'])) $items .= '<lastmod>' . $this->escape($entry['lastmod']) . '</lastmod>';
            $items .= '</url>';
        }
        return $this->xml('urlset', $items);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function xml(string $tag, string $items)
    {
        return response('<?xml version="1.0" encoding="UTF-8"?>' .
            '<' . $tag . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . $items . '</' . $tag . '>', 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                // Revalidate immediately: a profile may opt out or be blocked.
                'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff',
            ]);
    }
}
