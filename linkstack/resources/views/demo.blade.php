<?php use App\Models\UserData; ?>

<!DOCTYPE html>
@include('layouts.lang')
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">

  @php $GLOBALS['themeName'] = config('advanced-config.home_theme'); @endphp
  <?php
  // Theme Config
  function theme($key){
  $key = trim($key);
  $file = base_path('themes/' . $GLOBALS['themeName'] . '/config.php');
    if (file_exists($file)) {
      $config = include $file;
    if (isset($config[$key])) {
      return $config[$key];
  }}
  return null;}

  // Theme Custom Asset
  function themeAsset($path){
  $path = url('themes/' . $GLOBALS['themeName'] . '/extra/custom-assets/' . $path);
  return $path;}
  ?>

  <!-- Custom icons font-awesome -->
  <script>{!! file_get_contents(base_path("assets/external-dependencies/fontawesome.js")) !!}</script>
  <style>{!! str_replace('../', 'studio/', file_get_contents(base_path("assets/external-dependencies/fontawesome.css"))) !!}</style>

  @include('layouts.fonts')

  @if(theme('enable_custom_code') == "true" and theme('enable_custom_head') == "true" and env('ALLOW_CUSTOM_CODE_IN_THEMES') == 'true')@include($GLOBALS['themeName'] . '.extra.custom-head')@endif

  @if($GLOBALS['themeName'] != '' and $GLOBALS['themeName'] != 'default')
  <link rel="stylesheet" href="themes/{{$GLOBALS['themeName']}}/share.button.css">
  @if(theme('use_default_buttons') == "true")
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/brands.css') }}">
  @else
  <link rel="stylesheet" href="themes/{{$GLOBALS['themeName']}}/brands.css">
  @endif
  <link rel="stylesheet" href="themes/{{$GLOBALS['themeName']}}/skeleton-auto.css">
@if(file_exists(base_path('themes/' . $GLOBALS['themeName'] . '/animations.css')))
  <link rel="stylesheet" href="<?php echo asset('themes/' . $GLOBALS['themeName'] . '/animations.css') ?>">
@else
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/animations.css') }}">
@endif

@else
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/share.button.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/animations.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/brands.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/skeleton-auto.css') }}">
@endif
<style>.container{word-break: break-word;}</style>

  <meta name="robots" content="noindex, follow">

<style>.container{word-break: break-word;}</style>
@if(request()->query('preview') === 'home')
<link rel="stylesheet" href="{{ asset('assets/linkstack/css/skeleton-auto.css') }}">
<link rel="stylesheet" href="{{ asset('assets/linkstack/css/brands.css') }}">
<style>
html{height:100%}html,body{margin:0;min-height:100%;width:100%}body.lf-demo{background:#fff;color:#171717;font-family:system-ui,sans-serif}.lf-demo *{box-sizing:border-box}.lf-carousel{width:100%;min-height:100vh;display:flex;flex-direction:column;justify-content:center;padding:16px 0}.lf-slides{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;overscroll-behavior-x:contain;scrollbar-width:none;touch-action:pan-x pan-y}.lf-slides::-webkit-scrollbar{display:none}.lf-slide{flex:0 0 100%;min-width:0;scroll-snap-align:center;text-align:center;padding:8px 20px}.lf-avatar{width:70px;height:70px;margin:0 auto 10px;border-radius:50%;display:grid;place-items:center;overflow:hidden}.lf-avatar svg{width:100%;height:100%}.lf-slide h1{font-size:19px;line-height:1.2;margin:0 0 5px}.lf-slide p{font-size:12px;line-height:1.45;margin:0 auto 13px;max-width:230px}.lf-slide .button{display:flex;justify-content:center;align-items:center;width:100%;max-width:230px;min-height:38px;height:auto;margin:0 auto 8px;padding:9px 6px;font-size:12px;line-height:1.3;white-space:normal}.lf-slide h2{font-size:13px;margin:12px 0 7px}.lf-slide .lf-bio{background:#f2f3f5;border-radius:9px;padding:10px}.lf-controls{display:flex;align-items:center;justify-content:center;gap:12px;margin:10px 0 0}.lf-controls button{display:grid;place-items:center;width:32px;height:32px;min-height:0;margin:0;padding:0;border:1px solid #b7b7b7;border-radius:50%;background:#fff;color:#222;font:20px/1 system-ui;cursor:pointer}.lf-counter{font:12px system-ui;color:#555}.lf-hint{text-align:center;font:11px system-ui;color:#666;margin:6px 0 0}@media(max-height:400px){.lf-carousel{justify-content:flex-start}}
</style>
@endif
</head>
<body @if(request()->query('preview') === 'home') class="lf-demo" @endif>
@if(request()->query('preview') === 'home')
<div class="lf-carousel">
<div class="lf-slides" aria-label="Exemplos de páginas" tabindex="0">
<section class="lf-slide" aria-label="Exemplo 1 de 3"><div class="lf-avatar"><svg viewBox="0 0 80 80" role="img" aria-label="Avatar ilustrado"><circle cx="40" cy="40" r="40" fill="#cce5d7"/><path d="M13 80c2-19 12-27 27-27s25 8 27 27" fill="#382b28"/><circle cx="40" cy="34" r="18" fill="#d8986f"/><path d="M22 32c0-17 8-23 19-23 13 0 20 10 18 23-5-5-8-12-9-15-7 9-18 13-28 15" fill="#382b28"/><circle cx="34" cy="35" r="1.3"/><circle cx="46" cy="35" r="1.3"/><path d="M35 43q5 4 10 0" fill="none" stroke="#7d4940" stroke-width="1.5" stroke-linecap="round"/></svg></div><h1>Marina Costa</h1><p>Criadora de conteúdo · Ideias, rotina e inspiração</p><span class="button button-instagram">Instagram</span><span class="button button-youtube">YouTube</span><span class="button button-default">Meu site</span><span class="button button-default">Entre em contato</span></section>
<section class="lf-slide" aria-label="Exemplo 2 de 3"><div class="lf-avatar"><svg viewBox="0 0 80 80" role="img" aria-label="Avatar ilustrado"><circle cx="40" cy="40" r="40" fill="#f3ddbe"/><path d="M13 80c2-19 12-27 27-27s25 8 27 27" fill="#252326"/><circle cx="40" cy="34" r="18" fill="#a86d4f"/><path d="M22 32c0-17 8-23 19-23 13 0 20 10 18 23-5-5-8-12-9-15-7 9-18 13-28 15" fill="#252326"/><circle cx="34" cy="35" r="1.3"/><circle cx="46" cy="35" r="1.3"/><path d="M35 43q5 4 10 0" fill="none" stroke="#7d4940" stroke-width="1.5" stroke-linecap="round"/></svg></div><h1>Lucas Oliveira</h1><p>Fotógrafo · Transformando momentos em histórias</p><h2>Meu trabalho</h2><span class="button button-default">Ver portfólio</span><span class="button button-instagram">Instagram</span><h2>Vamos conversar?</h2><span class="button button-default">Enviar e-mail</span><span class="button button-default">Salvar contato (vCard)</span></section>
<section class="lf-slide" aria-label="Exemplo 3 de 3"><div class="lf-avatar"><svg viewBox="0 0 80 80" role="img" aria-label="Avatar ilustrado"><circle cx="40" cy="40" r="40" fill="#e4d4ee"/><path d="M13 80c2-19 12-27 27-27s25 8 27 27" fill="#4f2f25"/><circle cx="40" cy="34" r="18" fill="#e2b592"/><path d="M22 32c0-17 8-23 19-23 13 0 20 10 18 23-5-5-8-12-9-15-7 9-18 13-28 15" fill="#4f2f25"/><circle cx="34" cy="35" r="1.3"/><circle cx="46" cy="35" r="1.3"/><path d="M35 43q5 4 10 0" fill="none" stroke="#7d4940" stroke-width="1.5" stroke-linecap="round"/></svg></div><h1>Ateliê da Ana</h1><p>Feito à mão, com carinho para você.</p><h2>Bem-vindo ao meu espaço</h2><p class="lf-bio">Conheça minhas criações e acompanhe as novidades.</p><span class="button button-default">Ver catálogo</span><span class="button button-instagram">Instagram</span><span class="button button-default">Ligar para o ateliê</span></section>
</div><div class="lf-controls"><button type="button" class="lf-prev" aria-label="Exemplo anterior">‹</button><span class="lf-counter" aria-live="polite">1 / 3</span><button type="button" class="lf-next" aria-label="Próximo exemplo">›</button></div><p class="lf-hint">Deslize para conhecer outros exemplos</p></div>
<script>(function(){var slides=document.querySelector('.lf-slides'),items=slides.querySelectorAll('.lf-slide'),counter=document.querySelector('.lf-counter'),index=0;function show(n){index=(n+items.length)%items.length;slides.scrollTo({left:items[index].offsetLeft-slides.offsetLeft,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});counter.textContent=(index+1)+' / '+items.length}document.querySelector('.lf-prev').addEventListener('click',function(){show(index-1)});document.querySelector('.lf-next').addEventListener('click',function(){show(index+1)});slides.addEventListener('scroll',function(){index=Math.max(0,Math.min(items.length-1,Math.round(slides.scrollLeft/slides.clientWidth)));counter.textContent=(index+1)+' / '+items.length},{passive:true});})();</script>
@else

@if(theme('enable_custom_code') == "true" and theme('enable_custom_body') == "true" and env('ALLOW_CUSTOM_CODE_IN_THEMES') == 'true')@include($GLOBALS['themeName'] . '.extra.custom-body')@endif

@if($GLOBALS['themeName'] != '' and $GLOBALS['themeName'] != 'default')
    <!-- Enables parallax background animations -->
    <div class="background-container">
    <section class="parallax-background">
      <div id="object1" class="object1"></div>
      <div id="object2" class="object2"></div>
      <div id="object3" class="object3"></div>
      <div id="object4" class="object4"></div>
      <div id="object5" class="object5"></div>
      <div id="object6" class="object6"></div>
      <div id="object7" class="object7"></div>
      <div id="object8" class="object8"></div>
      <div id="object9" class="object9"></div>
      <div id="object10" class="object10"></div>
      <div id="object11" class="object11"></div>
      <div id="object12" class="object12"></div>
    </section>
    </div>
    <!-- End of parallax background animations -->
@endif

  <div class="container">
    <div class="row">
      <div class="column" style="margin-top: 15%">

        @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
        <img alt="avatar" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" width="auto" height="128px">
        @else
        <div class="logo-container fadein">
          <img src="{{ asset('assets/linkstack/images/logo.svg') }}" alt="Logo" style="width:150px; height:150px;">
        </div>
        @endif

        <h1 class="fadein">{{ config('app.name') }}</h1>


        <style>.description-parent * {margin-bottom: 1em;}.description-parent {padding-bottom: 30px;}</style>
        <center><div class="fadein description-parent"><p class="fadein">{{__('messages.Example page')}}</p></div></center>

        {{-- <!-- Icons -->
        @php $icons = DB::table('links')->where('user_id', $userinfo->id)->where('button_id', 94)->get(); @endphp
        <div class="row fadein social-icon-div">
        @foreach($icons as $icon)
        <a class="social-hover social-link" href="{{ route('clickNumber') . '/' . $icon->id. "?" . $icon->link}}" title="{{ucfirst($icon->title)}}" aria-label="{{ucfirst($icon->title)}}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif><i class="social-icon fa-brands fa-{{$icon->title}}"></i></a>
        @endforeach
        </div> --}}

        <!-- Buttons -->
        <?php function strp($urlStrp){return str_replace(array('http://', 'https://'), '', $urlStrp);} ?>
        <?php $initial=1; // <-- Effectively sets the initial loading time of the buttons. This value should be left at 1. ?>
        @if(config('advanced-config.use_custom_buttons') == 'true')
                <?php $array = config('advanced-config.buttons'); ?>
                @foreach($array as $button)
                 @php $linkName = str_replace('default ','',$button['button']) @endphp
                 @if($button['button'] === "custom" and ($button['custom_css'] === "" or $button['custom_css'] === "NULL") or (theme('allow_custom_buttons') == "false" and $button['button'] === "custom"))
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-{{ $button['button'] }} button button-hover icon-hover" rel="noopener noreferrer nofollow" href="{{ $button['link'] }}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif >@if($button['icon'] == 'llc')<img alt="button-icon" class="icon hvr-icon" src="{{ asset('\/assets/linkstack/icons\/')}}llc.svg">@else<i style="color: {{$button['icon']}}" class="icon hvr-icon fa {{$button['icon']}}"></i>@endif {{ $button['title'] }}</a></div>
                 @elseif($button['button'] === "custom" and $button['custom_css'] != "")
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-hover icon-hover" style="{{ $button['custom_css'] }}" rel="noopener noreferrer nofollow" href="{{ $button['link'] }}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif >@if($button['icon'] == 'llc')<img alt="button-icon" class="icon hvr-icon" src="{{ asset('\/assets/linkstack/icons\/')}}llc.svg">@else<i style="color: {{$button['icon']}}" class="icon hvr-icon fa {{$button['icon']}}"></i>@endif{{ $button['title'] }}</a></div>
                 @elseif($button['button'] === "buy me a coffee")
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-coffee button button-hover icon-hover" rel="noopener noreferrer nofollow" href="{{ $button['link'] }}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif ><img alt="button-icon" class="icon hvr-icon" src="@if(theme('use_custom_icons') == "true"){{ url('themes/' . $GLOBALS['themeName'] . '/extra/custom-icons')}}/coffee{{theme('custom_icon_extension')}} @else{{ asset('\/assets/linkstack/icons\/')}}coffee.svg @endif">Buy me a Coffee</a></div>
                 @elseif($button['button'] === "custom_website" and ($button['custom_css'] === "" or $button['custom_css'] === "NULL") or (theme('allow_custom_buttons') == "false" and $button['button'] === "custom_website"))
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-custom_website button button-hover icon-hover" rel="noopener noreferrer nofollow" href="{{ $button['link'] }}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif ><img alt="button-icon" class="icon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($button['id']))){{url('assets/favicon/icons/'.localIcon($button['id']))}}@else{{getFavIcon($button['id'])}}@endif">{{ $button['title'] }}</a></div>
                 @elseif($button['button'] === "custom_website" and $button['custom_css'] != "")
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-hover icon-hover" style="{{ $button['custom_css'] }}" rel="noopener noreferrer nofollow" href="{{ $button['link'] }}" @if(theme('open_links_in_same_tab') != "true")target="_blank"@endif ><img alt="button-icon" class="icon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($button['id']))){{url('assets/favicon/icons/'.localIcon($button['id']))}}@else{{getFavIcon($button['id'])}}@endif">{{ $button['title'] }}</a></div>
                 @elseif($button['button'] === "space")
                 <?php
                  if (is_numeric($button['title']) and $button['title'] < 10)
                  echo str_repeat("<br>",$button['title']);
                  elseif (is_numeric($button['title']) and $button['title'] >= 10)
                  echo str_repeat("<br>",10);
                  else
                  echo "<br><br><br>"
                  ?>
                 @elseif($button['button'] === "heading")
                 <h2>{{ $button['title'] }}</h2>
                 @else
                 <div style="--delay: {{ $initial++ }}s" class="button-entrance"><a class="button button-{{ $button['button'] }} button button-hover icon-hover" @if($button['link'] != '') href="{{ $button['link'] }}" target="_blank"@endif><img alt="button-icon" class="icon hvr-icon" src="{{ asset('\/assets/linkstack/icons\/') . $linkName }}.svg">{{ ucfirst($linkName) }}</a></div>
                 @endif
                @endforeach
        @else
                <div style="--delay: {{ $initial++ }}s" class="button-entrance"><div class="button button-github button button-hover icon-hover"><img alt="button-icon" class="icon hvr-icon" src="{{ asset('assets/linkstack/icons/github.svg') }}">Github</div></div>
                <div style="--delay: {{ $initial++ }}s" class="button-entrance"><div class="button button-twitter button button-hover icon-hover"><img alt="button-icon" class="icon hvr-icon" src="{{ asset('assets/linkstack/icons/twitter.svg') }}">Twitter</div></div>
                <div style="--delay: {{ $initial++ }}s" class="button-entrance"><div class="button button-instagram button button-hover icon-hover"><img alt="button-icon" class="icon hvr-icon" src="{{ asset('assets/linkstack/icons/instagram.svg') }}">Instagram</div></div>
        @endif

      </div>
    </div>
  </div>

  @if(theme('enable_custom_code') == "true" and theme('enable_custom_body_end') == "true" and env('ALLOW_CUSTOM_CODE_IN_THEMES') == 'true')@include($GLOBALS['themeName'] . '.extra.custom-body-end')@endif

@endif
</body>
</html>
