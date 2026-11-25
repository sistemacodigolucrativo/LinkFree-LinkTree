<meta charset="utf-8">
@php
    $seoName = \App\Services\PublicSeo::plain($userinfo->name) ?: $littlelink_name;
    $seoDescription = \App\Services\PublicSeo::description($userinfo->littlelink_description, 'Encontre os links e canais de contato de ' . $seoName . '.');
    $seoCustom = env('CUSTOM_META_TAGS') === 'true';
    $seoCanonical = \App\Services\PublicSeo::canonical(\App\Services\PublicSeo::profileUrl(request(), $userinfo));
    $seoRobots = \App\Services\PublicSeo::robots(!empty($noIndex));
    $seoVersion = \App\Services\PublicSeo::profileCardVersion($userinfo, $seoName);
    $seoImage = route('socialPreview.profile', ['id' => $userinfo->id, 'v' => $seoVersion]);
    $seoSchema = ['@context' => 'https://schema.org', '@type' => 'ProfilePage',
        '@id' => $seoCanonical . '#profile', 'url' => $seoCanonical,
        'mainEntity' => ['@type' => 'Person', '@id' => $seoCanonical . '#person',
            'name' => $seoName, 'description' => $seoDescription, 'url' => $seoCanonical, 'image' => $seoImage]];
@endphp
@foreach($links as $link)
    @if(in_array($link->name, ['mastodon', 'firefish', 'streams', 'friendica'], true))
        <link href="{{ $link->link }}" rel="me">
    @endif
@endforeach
<meta name="description" content="{{ $seoCustom && trim((string) config('advanced-config.description')) !== '' ? \App\Services\PublicSeo::description(config('advanced-config.description'), $seoDescription) : $seoDescription }}">
<meta name="author" content="{{ $seoCustom && config('advanced-config.author') != '' ? config('advanced-config.author') : $seoName }}">
<meta name="viewport" content="{{ $seoCustom && config('advanced-config.viewport') != '' ? config('advanced-config.viewport') : 'width=device-width, initial-scale=1' }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
@if($seoCustom && config('advanced-config.twitter_creator') != '')
<meta name="twitter:creator" content="{{ config('advanced-config.twitter_creator') }}">
@endif
<meta property="og:type" content="profile">
<meta property="og:site_name" content="{{ config('app.name', 'LinkFree') }}">
<meta property="og:locale" content="{{ \App\Services\PublicSeo::locale() }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:title" content="{{ $seoName }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image" content="{{ $seoImage }}">
@if(str_starts_with($seoImage, 'https://'))
<meta property="og:image:secure_url" content="{{ $seoImage }}">
@endif
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Prévia da página pública de {{ $seoName }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $seoCanonical }}">
<meta name="twitter:title" content="{{ $seoName }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<meta name="twitter:image:alt" content="Prévia da página pública de {{ $seoName }}">
<script type="application/ld+json">{!! json_encode($seoSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@if(config('advanced-config.linkstack_title') != '' and env('HOME_URL') === '')
<title>{{ $userinfo->name }} {{ config('advanced-config.linkstack_title') }}</title>
@elseif(env('CUSTOM_META_TAGS') == 'true' and config('advanced-config.title') != '')
<title>{{ config('advanced-config.title') }}</title>
@elseif(env('HOME_URL') != '')
<title>{{ $userinfo->name }}</title>
@else
<title>{{ $seoName }} | {{ config('app.name') }} </title>
@endif

@include('components.favicon')
@include('components.favicon-extension')

@if(file_exists(base_path("assets/linkstack/images/").findFile('favicon')))
<link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
@else
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
@endif

@include('layouts.analytics')