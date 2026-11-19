<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Per-host search engine discovery. No restricted or blocked accounts are
 * listed; custom-profile domains never list another site's users.
 */
class SeoController extends Controller
{
    private const PAGE_SIZE = 4000;

    public function robots(Request $request)
    {
        // Robots files are discovery hints, not authentication or privacy controls.
        $lines = [
            'User-agent: *',
            'Allow: /@',
            'Allow: /social-preview/',
            'Disallow: /admin/',
            'Disallow: /studio/',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            'Disallow: /export-',
            'Disallow: /import-data',
            'Disallow: /going/',
            'Disallow: /info/',
            'Disallow: /demo-page',
            'Disallow: /theme/',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sitemap(Request $request)
    {
        if ($this->noIndex()) {
            return $this->xml($this->urlSet([]));
        }

        if ($this->customProfileDomain($request)) {
            return $this->xml($this->urlSet([url('/')]));
        }

        $count = $this->publicProfiles()->count();
        $pages = (int) ceil($count / self::PAGE_SIZE);
        $items = ['<sitemap><loc>' . $this->xmlEscape(url('/sitemap-home.xml'))
            . '</loc></sitemap>'];
        for ($page = 1; $page <= $pages; $page++) {
            $items[] = '<sitemap><loc>'
                . $this->xmlEscape(url('/sitemap-profiles/' . $page . '.xml'))
                . '</loc></sitemap>';
        }

        return $this->xml('<?xml version="1.0" encoding="UTF-8"?>'
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items) . '</sitemapindex>');
    }

    public function homeSitemap(Request $request)
    {
        if ($this->noIndex()) {
            return $this->xml($this->urlSet([]));
        }

        if ($this->customProfileDomain($request)) {
            return $this->xml($this->urlSet([url('/')]));
        }

        // Redirect-only installations do not have an indexable homepage.
        if (config('advanced-config.disable_home_page') === 'redirect'
            && env('HOME_URL') === '') {
            return $this->xml($this->urlSet([]));
        }

        $homepage = env('HOME_URL') !== ''
            ? url('/')
            : url(config('advanced-config.custom_home_url', '/') === '/home' ? '/' : '/');

        return $this->xml($this->urlSet([$homepage]));
    }

    public function profilesSitemap(Request $request, int $page)
    {
        if ($page < 1) abort(404);
        if ($this->noIndex() || $this->customProfileDomain($request)) {
            return $this->xml($this->urlSet([]));
        }

        $total = $this->publicProfiles()->count();
        if ($page > max(1, (int) ceil($total / self::PAGE_SIZE))) abort(404);
        $users = $this->publicProfiles()->select('littlelink_name', 'updated_at')
            ->orderBy('id')->skip(($page - 1) * self::PAGE_SIZE)
            ->take(self::PAGE_SIZE)->get();

        $entries = [];
        foreach ($users as $user) {
            $lastmod = $user->updated_at ? substr((string) $user->updated_at, 0, 10) : null;
            $entries[] = ['url' => route('littlelink', [
                'littlelink' => $user->littlelink_name,
            ]), 'lastmod' => $lastmod];
        }
        return $this->xml($this->urlSet($entries));
    }

    private function publicProfiles()
    {
        $query = User::query()
            ->whereNotNull('littlelink_name')
            ->where('littlelink_name', '<>', '')
            ->where(function ($query) {
                $query->whereNull('block')->orWhere('block', '<>', 'yes');
            });

        // A profile used as the homepage already has a canonical root URL.
        if (env('HOME_URL') !== '') {
            $query->where('littlelink_name', '<>', env('HOME_URL'));
        }
        return $query;
    }

    private function customProfileDomain(Request $request): bool
    {
        foreach ((array) config('advanced-config.custom_domains', []) as $customDomain) {
            if (is_array($customDomain)
                && strtolower((string) ($customDomain['domain'] ?? '')) === strtolower($request->getHost())) {
                return true;
            }
        }
        return false;
    }

    private function noIndex(): bool
    {
        return stripos((string) config('advanced-config.robots', ''), 'noindex') !== false;
    }

    private function urlSet(array $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($items as $item) {
            $url = is_array($item) ? $item['url'] : $item;
            $xml .= '<url><loc>' . $this->xmlEscape($url) . '</loc>';
            if (is_array($item) && !empty($item['lastmod'])) {
                $xml .= '<lastmod>' . $this->xmlEscape($item['lastmod']) . '</lastmod>';
            }
            $xml .= '</url>';
        }
        return $xml . '</urlset>';
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xml(string $body)
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
