#!/usr/bin/env python3
"""Static SEO contracts. Does not test the authenticated or public VPS."""
from pathlib import Path

root = Path(__file__).resolve().parents[1] / "linkstack"
views = root / "resources/views"
profile = (views / "linkstack/modules/meta.blade.php").read_text(encoding="utf-8")
home = (views / "home.blade.php").read_text(encoding="utf-8")
routes = (root / "routes/web.php").read_text(encoding="utf-8")
image = (root / "app/Http/Controllers/SocialPreviewController.php").read_text(encoding="utf-8")
sitemap = (root / "app/Http/Controllers/SeoController.php").read_text(encoding="utf-8")
users = (root / "app/Http/Controllers/UserController.php").read_text(encoding="utf-8")

for label, source in (("profile", profile), ("home", home)):
    for tag in ('og:title', 'og:description', 'og:url', 'og:image', 'og:image:width',
                'og:image:height', 'og:image:type', 'twitter:card', 'twitter:image',
                'rel="canonical"', 'name="robots"', 'application/ld+json'):
        assert tag in source, (label, tag)
    assert 'image/jpeg' in source, label
    assert 'max-image-preview:large' in source, label
    assert 'JSON_HEX_TAG' in source, (label, "JSON-LD encoding")

assert "route('socialPreview.profile'" in profile
assert "'@type' => 'ProfilePage'" in profile
assert "'@type' => 'Person'" in profile
assert "env('CUSTOM_META_TAGS')" in profile
assert "config('advanced-config.canonical_url'" in profile
assert "route('socialPreview.site')" in home
assert "'@type' => 'WebSite'" in home
for marker in ("lf-hero", "linkfree-apresentacao.webp", "lf-theme-gallery"):
    assert marker in home, ("Home visual content removed", marker)

for route_name in ("socialPreview.profile", "socialPreview.site", "seo.robots",
                   "seo.sitemap", "seo.sitemap.home", "seo.sitemap.profiles"):
    assert "->name('" + route_name + "')" in routes, route_name
assert "->middleware('disableCookies')" in routes

assert "WIDTH = 1200" in image and "HEIGHT = 630" in image
assert "imagejpeg($canvas, null, 86)" in image
assert "'Content-Type' => 'image/jpeg'" in image
assert "imagecreatefromwebp" in image
assert "realpath(base_path('assets/img'))" in image
assert "$user->block === 'yes'" in image
assert "Cache-Control" in image and "ETag" in image

assert "PAGE_SIZE = 4000" in sitemap
assert "where('block', '<>', 'yes')" in sitemap
assert "whereNotNull('littlelink_name')" in sitemap
assert "customProfileDomain($request)" in sitemap
assert "'noindex'" in sitemap
assert "Sitemap: " in sitemap
assert "url('/sitemap.xml')" in sitemap
assert "Disallow: /admin/" in sitemap
assert "application/xml" in sitemap and "text/plain" in sitemap
assert "'block', 'updated_at'" in users, "Missing last-updated version data"

print("PASS: SEO source contracts, social JPEG, profiles, sitemap and robots")
