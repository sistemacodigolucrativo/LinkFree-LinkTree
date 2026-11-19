#!/usr/bin/env python3
"""Check the committed LinkFree artwork and default/custom branding contracts."""
from pathlib import Path
from xml.etree import ElementTree

root = Path(__file__).resolve().parents[1] / "linkstack"
images = root / "assets/linkstack/images"
for filename, size, has_white in (("logo.svg", 850, True), ("favicon.svg", 512, False)):
    source = (images / filename).read_text(encoding="utf-8")
    image = ElementTree.fromstring(source)
    assert image.tag.endswith("svg")
    assert image.attrib.get("viewBox") == f"0 0 {size} {size}"
    assert 'fill="#39F713"' in source and 'fill="#000"' in source
    assert ("#FFFFFF" in source) is has_white
    assert 'fill-rule="evenodd"' in source

views = root / "resources/views"
fallbacks = (
    "layouts/sidebar.blade.php", "layouts/guest.blade.php",
    "layouts/installing.blade.php", "layouts/updater.blade.php",
    "linkstack/modules/meta.blade.php", "home.blade.php",
    "maintenance.blade.php", "pages.blade.php",
    "report.blade.php", "linkinfo.blade.php",
)
for name in fallbacks:
    s = (views / name).read_text(encoding="utf-8")
    assert "findFile('favicon')" in s, (name, "custom favicon removed")
    assert 'assets/linkstack/images/favicon.svg' in s, (name, "fallback missing")
    assert '<link rel="icon" type="image/svg+xml" href="{{ asset(\'assets/linkstack/images/logo.svg\') }}">' not in s, (name, "logo incorrectly used as favicon")
home = (views / "home.blade.php").read_text(encoding="utf-8")
assert "route('socialPreview.site')" in home, "Homepage must use JPEG social preview"
assert 'logo-animated.svg' not in home, "Old landing logo still selected"
meta = (views / "linkstack/modules/meta.blade.php").read_text(encoding="utf-8")
assert "route('socialPreview.profile')" in meta, "Public profiles must use JPEG social preview"
for name in ("layouts/sidebar.blade.php", "auth/login.blade.php"):
    s = (views / name).read_text(encoding="utf-8")
    assert "findFile('avatar')" in s, (name, "admin brand override removed")
for name in ("auth/login.blade.php", "studio/page.blade.php", "linkstack/elements/avatar.blade.php"):
    s = (views / name).read_text(encoding="utf-8")
    assert 'src="{{ asset(\'assets/linkstack/images/favicon.svg\') }}"' in s, (name, "small icon fallback missing")
print("PASS default LinkFree wordmark, favicon, brand overrides, and social preview")
