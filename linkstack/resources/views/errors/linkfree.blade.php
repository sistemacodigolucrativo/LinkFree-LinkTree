<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>{{ $statusCode }} — {{ config('app.name', 'LinkFree') }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
  <style>
    :root{color-scheme:light dark;--bg:#f2f7f4;--fg:#18362b;--surface:#fff;--muted:#4d645a;--accent:#276641;--stroke:#c5d6cc}
    @media(prefers-color-scheme:dark){:root{--bg:#101c17;--fg:#e5f5e9;--surface:#1a2b23;--muted:#c2d4c7;--accent:#b3ecbb;--stroke:#475a4e}}
    *{box-sizing:border-box}
    html,body{min-height:100%;margin:0}
    body{font:normal 1rem/1.65 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--fg);display:grid;place-items:center;padding:clamp(16px,4vw,36px)}
    main{width:min(100%,560px);text-align:center;background:var(--surface);border:1px solid var(--stroke);border-radius:22px;padding:clamp(25px,5vw,52px);box-shadow:0 12px 40px #0001}
    .brand{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:20px;font-weight:800;overflow-wrap:anywhere}
    .brand img{width:44px;height:44px;object-fit:contain}
    .code{font-size:clamp(3.5rem,12vw,5rem);font-weight:850;line-height:1.1;margin:12px 0;color:var(--accent)}
    h1{font-size:clamp(1.3rem,4vw,1.8rem);line-height:1.3;margin:12px 0}
    p{color:var(--muted);margin:0 0 22px}
    .back{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:12px 24px;border-radius:11px;background:var(--accent);color:var(--surface);font-weight:750;text-decoration:none}
    @media(prefers-color-scheme:dark){.back{color:#15231a}}
    .back:focus-visible{outline:3px solid var(--fg);outline-offset:4px}
  </style>
</head>
<body>
<main aria-labelledby="lf-error-title">
 <div class="brand"><img src="{{ asset('assets/linkstack/images/favicon.svg') }}" alt=""><span>{{ config('app.name', 'LinkFree') }}</span></div>
 <p class="code" aria-label="{{ $statusCode }}">{{ $statusCode }}</p>
 <h1 id="lf-error-title">{{ $errorTitle }}</h1>
 <p>{{ $errorDescription }}</p>
 <a href="{{ url('/') }}" class="back">{{ __('messages.lf_error_back') }}</a>
</main>
</body>
</html>
