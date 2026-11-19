<meta charset="utf-8">

@php
    // Public profile details only; never include a user's email, session or secret.
    $relMeList = ['mastodon', 'firefish', 'streams', 'friendica'];
    $profileName = trim((string) $userinfo->name) ?: (string) $littlelink_name;
    $plainDescription = html_entity_decode(
        strip_tags((string) $userinfo->littlelink_description),
        ENT_QUOTES | ENT_HTML5, 'UTF-8'
    );
    $plainDescription = trim(preg_replace('/\s+/u', ' ', $plainDescription) ?: '');
    $seoDescription = mb_strimwidth(
        $plainDescription ?: 'Encontre os links e canais de contato de ' . $profileName . '.',
        0, 170, '…', 'UTF-8'
    );

    $canonicalUrl = route('littlelink', ['littlelink' => $littlelink_name]);
    foreach ((array) config('advanced-config.custom_domains', []) as $customDomain) {
        if (!is_array($customDomain) ||
            strtolower((string) ($customDomain['domain'] ?? '')) !== strtolower(request()->getHost())) {
            continue;
        }
        if ((isset($customDomain['name']) && (string) $customDomain['name'] === (string) $littlelink_name)
            || (isset($customDomain['id']) && (int) $customDomain['id'] === (int) $userinfo->id)) {
            $canonicalUrl = url('/');
        }
    }
    if (env('HOME_URL') !== '' && (string) env('HOME_URL') === (string) $littlelink_name) {
        $canonicalUrl = url('/');
    }
    $canonicalOverride = trim((string) config('advanced-config.canonical_url', ''));
    if (filter_var($canonicalOverride, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($canonicalOverride, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        $canonicalUrl = $canonicalOverride;
    }

    $avatar = findAvatar($userinfo->id);
    $avatarModified = $avatar !== 'error.error' && is_file(base_path($avatar))
        ? (string) (@filemtime(base_path($avatar)) ?: 0) : 'default';
    $imageVersion = substr(hash('sha256', $profileName . '|' . $plainDescription . '|'
        . (string) $userinfo->updated_at . '|' . $avatarModified), 0, 12);
    $socialImage = route('socialPreview.profile', ['id' => $userinfo->id, 'v' => $imageVersion]);
    $seoTitle = $profileName . ' | ' . config('app.name', 'LinkFree');
    $robotsDirective = trim((string) config('advanced-config.robots', 'index,follow'));
    $robotsDirective = $robotsDirective ?: 'index,follow';
    if (stripos($robotsDirective, 'noindex') === false
        && stripos($robotsDirective, 'max-image-preview') === false) {
        $robotsDirective .= ',max-image-preview:large';
    }
    $locale = str_replace('-', '_', app()->getLocale());
    if ($locale === 'pt') $locale = 'pt_BR';

    $profileSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        '@id' => $canonicalUrl . '#profile',
        'url' => $canonicalUrl,
        'mainEntity' => [
            '@type' => 'Person',
            '@id' => $canonicalUrl . '#person',
            'name' => $profileName,
            'description' => $seoDescription,
            'url' => $canonicalUrl,
            'image' => $socialImage,
        ],
    ];
@endphp

@foreach($links as $link)
    @if(in_array($link->name, $relMeList, true))
        <link href="{{ $link->link }}" rel="me">
    @endif
@endforeach

@if(env('CUSTOM_META_TAGS') == 'true')
    @include('layouts.meta')
@else
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="author" content="{{ $profileName }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
@endif

{{-- Do not duplicate a custom robots or canonical meta tag. --}}
@if(env('CUSTOM_META_TAGS') != 'true' || config('advanced-config.robots') == '')
<meta name="robots" content="{{ $robotsDirective }}">
@endif
@if(env('CUSTOM_META_TAGS') != 'true' || config('advanced-config.canonical_url') == '')
<link rel="canonical" href="{{ $canonicalUrl }}">
@endif

<meta property="og:type" content="profile">
<meta property="og:site_name" content="{{ config('app.name', 'LinkFree') }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $profileName }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image" content="{{ $socialImage }}">
@if(str_starts_with($socialImage, 'https://'))
<meta property="og:image:secure_url" content="{{ $socialImage }}">
@endif
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Prévia da página pública de {{ $profileName }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:title" content="{{ $profileName }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">
<meta name="twitter:image:alt" content="Prévia da página de {{ $profileName }}">

<script type="application/ld+json">{!! json_encode(
    $profileSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) !!}</script>

@if(config('advanced-config.linkstack_title') != '' and env('HOME_URL') === '')
<title>{{ $profileName }} {{ config('advanced-config.linkstack_title') }}</title>
@elseif(env('CUSTOM_META_TAGS') == 'true' and config('advanced-config.title') != '')
<title>{{ config('advanced-config.title') }}</title>
@elseif(env('HOME_URL') != '')
<title>{{ $profileName }}</title>
@else
<title>{{ $seoTitle }}</title>
@endif

@include('components.favicon')
@include('components.favicon-extension')

@if(file_exists(base_path("assets/linkstack/images/").findFile('favicon')))
<link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
@else
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
@endif

@include('layouts.analytics')
