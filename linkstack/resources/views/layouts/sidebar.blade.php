@php
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\User;
$usrhandl = Auth::user()->littlelink_name;
@endphp
<!doctype html>
@include('layouts.lang')
<html>
  <head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>{{env('APP_NAME')}}</title>

      <script src="{{asset('assets/js/detect-dark-mode.js')}}"></script>
      {{-- Fundo visivel antes de baixar CSS/JS: impede flash branco nas trocas de pagina. --}}
      <style>
        html{background:#f5f7fc}
        html.lf-prepaint-dark{background:#222738}
        html,body{min-height:100%;min-height:100dvh}
        body{background-color:inherit}
        /* O loader do template cobria toda a tela com branco ao navegar. */
        #loading{display:none!important}
        @view-transition{navigation:auto}
      </style>
      <script>
        (function(){
          var mode='auto';
          try{mode=localStorage.getItem('color-mode')||'auto'}catch(_){}
          var dark=mode==='dark'||(mode==='auto'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);
          if(dark)document.documentElement.classList.add('lf-prepaint-dark');
        })();
      </script>

      <base href="{{url()->current()}}" />

	  @include('layouts.analytics')
	  @stack('sidebar-stylesheets')
    @include('layouts.notifications')

    @php
    // Update the 'updated_at' timestamp for the currently authenticated user
    if (auth()->check()) {
        $user = auth()->user();
        $user->touch();
    }
    @endphp

      <!-- Favicon -->
      @if(file_exists(base_path("assets/linkstack/images/").findFile('favicon')))
      <link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
      @else
      <link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
      @endif

      <!-- Library / Plugin Css Build -->
      <link rel="stylesheet" href="{{asset('assets/css/core/libs.min.css')}}" />

      <!-- Aos Animation Css -->
      <link rel="stylesheet" href="{{asset('assets/vendor/aos/dist/aos.css')}}" />

      @include('layouts.fonts')

      <!-- Hope Ui Design System Css -->
      <link rel="stylesheet" href="{{asset('assets/css/hope-ui.min.css?v=2.0.0')}}" />

      <!-- Custom Css -->
      <link rel="stylesheet" href="{{asset('assets/css/custom.min.css?v=2.0.0')}}" />

      <!-- Dark Css -->
      <link rel="stylesheet" href="{{asset('assets/css/dark.min.css')}}" />

      <!-- Customizer Css -->
            @if(file_exists(base_path("assets/dashboard-themes/dashboard.css")))
      <link rel="stylesheet" href="{{asset('assets/dashboard-themes/dashboard.css')}}" />
      @else
      <link rel="stylesheet" href="{{asset('assets/css/customizer.min.css')}}" />
      @endif

      <!-- RTL Css -->
      <link rel="stylesheet" href="{{asset('assets/css/rtl.min.css')}}" />
      {{-- Shared high-contrast light/dark palette, after vendor theme styles. --}}
      <link rel="stylesheet" href="{{ asset('assets/css/linkfree-color-modes.css') }}?v={{ filemtime(base_path('assets/css/linkfree-color-modes.css')) }}" />
      <link rel="stylesheet" href="{{ asset('assets/css/linkfree-navigation-polish.css') }}?v={{ filemtime(base_path('assets/css/linkfree-navigation-polish.css')) }}" />
      <link rel="stylesheet" href="{{ asset('assets/css/linkfree-light-gradient.css') }}?v={{ filemtime(base_path('assets/css/linkfree-light-gradient.css')) }}" />

	  <meta name="csrf-token" content="{{ csrf_token() }}">
	  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/hover-min.css') }}">
	  <link rel="stylesheet" href="{{ asset('assets/linkstack/css/animate.css') }}">
	  <link rel="stylesheet" href="{{ asset('assets/external-dependencies/bootstrap-icons.css') }}">

  <style>:focus-visible{outline:3px solid var(--lf-focus);outline-offset:3px}[role="button"]{cursor:pointer}button[data-setting]{font:inherit} .backup-list .button-entrance{max-width:100%}</style>
  <style>
/* Cabeçalho slim: mantém saudação, nome e botões de perfil sem banner alto. */
.iq-navbar-header.welcome-slim{
 width:calc(100% - 28px);max-width:980px;min-height:0;height:auto!important;
 margin:12px auto 18px!important;padding:14px clamp(15px,3vw,25px)!important;
 border-radius:15px;overflow:visible;position:relative;box-shadow:0 8px 18px var(--lf-shadow-header)
}
.iq-navbar-header.welcome-slim .iq-container{padding:0!important;max-width:100%}
.iq-navbar-header.welcome-slim .row{margin:0!important}
.iq-navbar-header.welcome-slim .col-md-12{padding:0!important}
.iq-navbar-header.welcome-slim h1{
 font-size:clamp(1.18rem,3vw,1.7rem);line-height:1.25;margin:0 0 4px!important;
 overflow-wrap:anywhere
}
.iq-navbar-header.welcome-slim h5{
 font-size:clamp(.84rem,2.3vw,1rem);line-height:1.4;margin:0!important;opacity:.96
}
.iq-navbar-header.welcome-slim .iq-header-img{display:none!important}
.iq-navbar-header.welcome-slim .btn{min-height:40px;margin-top:5px}
/* No mobile, a barra de ferramentas original ocupa o lugar da antiga barra
   "LinkFree + menu hambúrguer". A seta [data-toggle=sidebar] continua ativa. */
@media(max-width:991.98px){
 .mobile-unified-nav .navbar-inner{
  width:100%;min-width:0;display:flex!important;flex-wrap:nowrap!important;
  gap:5px;align-items:center;padding:7px clamp(6px,2vw,16px)!important
 }
 .mobile-unified-nav .navbar-brand{display:none!important}
 .mobile-unified-nav .navbar-toggler{display:none!important}
 .mobile-unified-nav .sidebar-toggle{
  display:inline-flex!important;align-items:center;justify-content:center;
  flex:0 0 40px;min-width:40px;min-height:44px;margin:0!important;
  position:relative;z-index:1
 }
 .mobile-unified-nav #navbarSupportedContent{
  display:flex!important;flex:1 1 auto;min-width:0;width:auto!important;
  max-width:100%;height:auto!important;visibility:visible!important;
  align-items:center;justify-content:flex-end;overflow:visible
 }
 .mobile-unified-nav #navbarSupportedContent .navbar-list{
  display:flex!important;flex-direction:row!important;flex-wrap:nowrap!important;
  align-items:center!important;justify-content:flex-end;
  gap:0;margin-left:auto!important;margin-bottom:0!important;min-width:0;padding:0
 }
 .mobile-unified-nav #navbarSupportedContent .navbar-list>li{
  flex:0 0 auto;margin:0!important;min-width:0;position:relative
 }
 .mobile-unified-nav #navbarSupportedContent .navbar-list .nav-link{
  min-height:40px;display:inline-flex;align-items:center;justify-content:center;padding:5px!important
 }
 .mobile-unified-nav #navbarSupportedContent .navbar-list .dropdown-menu{
  max-width:min(94vw,360px);z-index:1060
 }
 .mobile-unified-nav #navbarDropdown .caption{display:none!important}
 .mobile-unified-nav #navbarDropdown .avatar{width:34px!important;height:34px!important;object-fit:cover}
 .mobile-unified-nav #navbarSupportedContent .navbar-list .btn.btn-sm{
  font-size:.78rem;line-height:1.2;padding:9px 7px!important;min-height:40px
 }
 .mobile-unified-nav #navbarSupportedContent .navbar-list .btn-seg-ico{font-size:.85rem}
 .iq-navbar-header.welcome-slim{
  width:calc(100% - 16px);margin:8px auto 12px!important;
  padding:12px 14px!important
 }
}
@media(max-width:359px){
 .mobile-unified-nav .navbar-inner{gap:2px;padding-inline:3px!important}
 .mobile-unified-nav .sidebar-toggle{min-width:34px;flex-basis:34px}
 .mobile-unified-nav #navbarSupportedContent .navbar-list .nav-link{padding:3px!important}
 .mobile-unified-nav #navbarSupportedContent .navbar-list .btn.btn-sm{
  padding:8px 4px!important;font-size:.71rem
 }
 .mobile-unified-nav #navbarDropdown .avatar{width:30px!important;height:30px!important}
}

/* Revisão do cabeçalho (anotação 01): sem barra extra nem sobreposição.
 * O tema original usava margem de -3rem (ou +4em no mobile) para um banner
 * de 205px; agora o banner é slim e essa compensação não é válida. */
.main-content > .iq-banner:not(.hide) + .content-inner,
.main-content > .iq-banner:not(.hide) + .conatiner-fluid.content-inner{
 margin-top:0!important;padding-top:0!important;
}
.iq-navbar-header.welcome-slim{clear:both}
@media(max-width:991.98px){
 .main-content > .iq-banner{display:flow-root;position:relative;margin:0!important;padding:0!important}
 .main-content .mobile-unified-nav{
  position:relative!important;z-index:889;
  width:100%!important;min-height:56px;height:auto!important;
  margin:0!important;padding:0!important;border-radius:0!important;
  background:var(--lf-surface);
  border-bottom:1px solid var(--lf-border);box-shadow:none;
 }
 .main-content .mobile-unified-nav > .navbar-inner{
  display:grid!important;grid-template-columns:44px minmax(0,1fr);
  column-gap:6px;row-gap:0;align-items:center;
  width:100%!important;max-width:100%;min-height:56px;height:auto!important;
  margin:0!important;padding:4px 10px!important;overflow:visible;
 }
 .main-content .mobile-unified-nav .navbar-brand,
 .main-content .mobile-unified-nav .navbar-toggler{display:none!important}
 .main-content .mobile-unified-nav .sidebar-toggle{
  position:static!important;inset:auto!important;transform:none!important;
  grid-column:1;grid-row:1;order:0;
  display:flex!important;align-items:center;justify-content:center;
  width:44px!important;height:44px!important;min-width:44px!important;
  margin:0!important;padding:9px!important;border:1px solid var(--lf-border)!important;
  border-radius:10px!important;background:var(--lf-raised)!important;
  color:var(--lf-text)!important;box-shadow:none!important;
 }
 .main-content .mobile-unified-nav .sidebar-toggle .icon{transform:none!important}
 .main-content .mobile-unified-nav #navbarSupportedContent{
  grid-column:2;grid-row:1;min-width:0!important;max-width:100%!important;
  width:100%!important;flex:1 1 auto;align-self:center;
  display:flex!important;align-items:center!important;justify-content:flex-end!important;
  visibility:visible!important;opacity:1!important;position:static!important;
  height:auto!important;max-height:none!important;min-height:44px;
  margin:0!important;padding:0!important;border:0!important;
  background:transparent!important;box-shadow:none!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent > .navbar-list{
  display:flex!important;flex-flow:row nowrap!important;gap:1px!important;
  justify-content:flex-end!important;align-items:center!important;
  width:100%;min-width:0!important;max-width:100%;height:auto;
  margin:0!important;padding:0!important;list-style:none;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent > .navbar-list > li{
  float:none!important;position:relative;flex:0 0 auto;min-width:0!important;
  display:flex;align-items:center;justify-content:center;margin:0!important;padding:0!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent .nav-link{
  width:auto;min-width:32px;min-height:44px;max-height:48px;
  padding:6px 3px!important;margin:0!important;color:var(--lf-text);
 }
 .main-content .mobile-unified-nav #navbarSupportedContent .btn.btn-sm{
  min-height:40px!important;font-size:clamp(.68rem,2.5vw,.78rem)!important;
  margin:0!important;padding:8px 5px!important;white-space:nowrap;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent #navbarDropdown{
  min-width:38px;padding-inline:2px!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent #navbarDropdown img{
  width:32px!important;height:32px!important;min-width:32px;
  object-fit:cover;border-radius:50%;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent .dropdown-menu{
  position:absolute;z-index:1060;max-width:min(96vw,340px);
 }
 .main-content .iq-navbar-header.welcome-slim{
  margin:10px auto 12px!important;padding:11px 14px!important;
  width:calc(100% - 20px);max-width:100%;border-radius:12px;
 }
 .main-content .iq-banner:not(.hide)+.content-inner{margin-top:0!important}
}
@media(max-width:359px){
 .main-content .mobile-unified-nav>.navbar-inner{
  grid-template-columns:38px minmax(0,1fr);column-gap:2px;padding:4px 5px!important;
 }
 .main-content .mobile-unified-nav .sidebar-toggle{
  width:38px!important;min-width:38px!important;height:42px!important;padding:7px!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent .nav-link{
  min-width:29px;padding:5px 2px!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent .btn.btn-sm{padding:8px 3px!important}
 .main-content .mobile-unified-nav #navbarSupportedContent #navbarDropdown{
  min-width:32px!important;
 }
 .main-content .mobile-unified-nav #navbarSupportedContent #navbarDropdown img{
  width:29px!important;height:29px!important;min-width:29px;
 }
}
/* Seta do menu lateral na barra superior: apenas o ícone, sem cartão. */
@media(max-width:991.98px){
 .main-content .mobile-unified-nav .sidebar-toggle{
   width:40px!important;min-width:40px!important;height:40px!important;
   padding:9px!important;margin:0!important;
   border:0!important;background:transparent!important;
   box-shadow:none!important;border-radius:0!important;
   color:var(--lf-text)!important;
 }
 .main-content .mobile-unified-nav .sidebar-toggle svg,
 .main-content .mobile-unified-nav .sidebar-toggle .icon-20{
   width:16px!important;height:16px!important;
   max-width:16px!important;max-height:16px!important;
 }
 .main-content .mobile-unified-nav .sidebar-toggle:hover{
   background:transparent!important;opacity:.85;
 }
}
@media(max-width:359px){
 .main-content .mobile-unified-nav .sidebar-toggle{
   width:38px!important;min-width:38px!important;height:40px!important;
 }
}
</style>
</head>
  <body class="  ">
    <!-- loader Start -->
    <div id="loading">
      <div class="loader simple-loader">
          <div class="loader-body"></div>
      </div>    </div>
    <!-- loader END -->

    <aside class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all ">
        <div class="sidebar-header d-flex align-items-center justify-content-start">
            <a href="{{ route('panelIndex') }}" class="navbar-brand">

                <!--Logo start-->
                <div class="logo-main">
                @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
                <div class="logo-normal">
                  <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;" alt="Logotipo LinkFree">
              </div>
              <div class="logo-mini">
                <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;" alt="Logotipo LinkFree">
              </div>
                @else
                <div class="logo-normal">
                  <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/favicon.svg') }}" width="30px" height="30px" alt="Logotipo LinkFree">
              </div>
              <div class="logo-mini">
                <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/favicon.svg') }}" width="30px" height="30px" alt="Logotipo LinkFree">
              </div>
                @endif
                </div>
                <!--logo End-->

                <h4 class="logo-title">{{env('APP_NAME')}}</h4>
            </a>
            <button type="button" class="sidebar-toggle" data-toggle="sidebar" data-active="true" aria-label="Recolher menu lateral" aria-expanded="true" data-label-expanded="Recolher menu lateral" data-label-collapsed="Expandir menu lateral" style="border:0;background:transparent;padding:0;">
                <i class="icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.25 12.2744L19.25 12.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </i>
            </button>
        </div>
        <div class="sidebar-body pt-0" id="sidebar-scroll-region" tabindex="0" aria-label="Navegação lateral rolável">
            <div class="sidebar-list">
                <!-- Sidebar Menu Start -->
                <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
@php
  $navLinksOpen = request()->is('studio/links*') || request()->is('studio/page-icons') || request()->is('studio/blocks') || request()->is('studio/add-link') || request()->is('studio/edit-link/*') || request()->is('studio/button-editor/*');
  $navAppearanceOpen = request()->is('studio/theme') || request()->is('studio/theme-preview/*');
@endphp

<li class="nav-item">
  <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('panelIndex') }}">
    <i class="icon bi bi-speedometer2" aria-hidden="true"></i>
    <span class="item-name">Painel de Controle</span>
  </a>
</li>
<li class="nav-item nav-accordion-group">
  <a class="nav-link nav-accordion-toggle {{ $navLinksOpen ? 'active' : '' }}" href="#nav-links" data-bs-toggle="collapse" role="button" aria-expanded="{{ $navLinksOpen ? 'true' : 'false' }}" aria-controls="nav-links">
    <i class="icon bi bi-link-45deg" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Nav Link Management')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $navLinksOpen ? 'show' : '' }}" id="nav-links" data-bs-parent="#sidebar-menu">
<li class="nav-item"><a class="nav-link {{ request()->is('studio/links*') || request()->is('studio/edit-link/*') || request()->is('studio/button-editor/*') ? 'active' : '' }}" href="{{ url('/studio/links') }}"><i class="icon bi bi-link-45deg" aria-hidden="true"></i><span class="item-name">{{__('messages.Links')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('studio/page-icons') ? 'active' : '' }}" href="{{ route('showPageIcons') }}"><i class="icon bi bi-images" aria-hidden="true"></i><span class="item-name">Ícones de Página</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('studio/blocks') ? 'active' : '' }}" href="{{ route('showBlocks') }}"><i class="icon bi bi-grid-3x3-gap" aria-hidden="true"></i><span class="item-name">Gerenciar blocos</span></a></li>
  </ul>
</li>
<li class="nav-item nav-accordion-group">
  <a class="nav-link nav-accordion-toggle {{ $navAppearanceOpen ? 'active' : '' }}" href="#nav-appearance" data-bs-toggle="collapse" role="button" aria-expanded="{{ $navAppearanceOpen ? 'true' : 'false' }}" aria-controls="nav-appearance">
    <i class="icon bi bi-palette" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Personalization')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $navAppearanceOpen ? 'show' : '' }}" id="nav-appearance" data-bs-parent="#sidebar-menu">
<li class="nav-item"><a class="nav-link {{ request()->is('studio/theme*') ? 'active' : '' }}" href="{{ url('/studio/theme') }}"><i class="icon bi bi-palette" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Themes Appearance')}}</span></a></li>
<li class="nav-item"><button type="button" class="nav-link lf-panel-theme-link" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><i class="icon bi bi-brush" aria-hidden="true"></i><span class="item-name">{{__('messages.Panel Theme')}}</span></button></li>
  </ul>
</li>

<li class="nav-item"><a class="nav-link {{ request()->is('studio/referrals*') ? 'active' : '' }}" href="{{ route('studio.referrals') }}"><i class="icon bi bi-people" aria-hidden="true"></i><span class="item-name">Minhas indicações</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('studio/verification*') ? 'active' : '' }}" href="{{ route('studio.verification') }}"><i class="icon bi bi-patch-check" aria-hidden="true"></i><span class="item-name">Selo verificado</span></a></li>

@if(auth()->check() && auth()->user()->role === 'admin')
@php
  $adminUsersOpen = request()->is('admin/users*') || request()->is('admin/edit-user/*') || request()->is('admin/links/*') || request()->is('admin/new-user') || request()->is('admin/referrals*') || request()->is('admin/verification*') || request()->is('admin/reports*');
  $adminSiteOpen = request()->is('admin/site*') || request()->is('admin/pages*');
  $adminThemesOpen = request()->is('admin/theme') || request()->is('admin/github-themes*') || request()->is('admin/linktype*');
  $adminSystemOpen = request()->is('admin/config') || request()->is('admin/backups') || request()->is('admin/advanced-config') || request()->is('admin/env') || request()->is('admin/phpinfo') || request()->is('update');
  $adminMenuOpen = $adminUsersOpen || $adminSiteOpen || $adminThemesOpen || $adminSystemOpen || request()->is('admin/*');
@endphp
<li class="nav-item nav-accordion-group nav-accordion-admin" id="nav-admin-panel">
  <a class="nav-link nav-accordion-toggle {{ $adminMenuOpen ? 'active' : '' }}" href="#admin-navigation" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminMenuOpen ? 'true' : 'false' }}" aria-controls="admin-navigation">
    <i class="icon bi bi-shield-lock" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Administration')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $adminMenuOpen ? 'show' : '' }}" id="admin-navigation" data-bs-parent="#sidebar-menu">
<li class="nav-item"><a class="nav-link {{ request()->is('admin/overview') ? 'active' : '' }}" href="{{ route('adminOverview') }}"><i class="icon bi bi-graph-up-arrow" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Overview')}}</span></a></li>
    <li class="nav-item nav-accordion-group nav-accordion-admin">
  <a class="nav-link nav-accordion-toggle {{ $adminUsersOpen ? 'active' : '' }}" href="#admin-users-menu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminUsersOpen ? 'true' : 'false' }}" aria-controls="admin-users-menu">
    <i class="icon bi bi-people-fill" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Nav Admin Users')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $adminUsersOpen ? 'show' : '' }}" id="admin-users-menu" data-bs-parent="#admin-navigation">
<li class="nav-item"><a class="nav-link {{ request()->is('admin/users*') || request()->is('admin/edit-user/*') || request()->is('admin/links/*') ? 'active' : '' }}" href="{{ route('showUsers') }}"><i class="icon bi bi-people-fill" aria-hidden="true"></i><span class="item-name">{{__('messages.Manage Users')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/new-user') ? 'active' : '' }}" href="{{ route('createNewUser') }}"><i class="icon bi bi-person-plus" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin New User')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/referrals*') ? 'active' : '' }}" href="{{ route('admin.referrals') }}"><i class="icon bi bi-people-fill" aria-hidden="true"></i><span class="item-name">Indicações</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/verification*') ? 'active' : '' }}" href="{{ route('admin.verification') }}"><i class="icon bi bi-patch-check" aria-hidden="true"></i><span class="item-name">Solicitações de selo</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ route('admin.abuse-reports.index') }}"><i class="icon bi bi-flag" aria-hidden="true"></i><span class="item-name">Central de denúncias</span></a></li>
  </ul>
</li>
    <li class="nav-item nav-accordion-group nav-accordion-admin">
  <a class="nav-link nav-accordion-toggle {{ $adminSiteOpen ? 'active' : '' }}" href="#admin-site-menu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminSiteOpen ? 'true' : 'false' }}" aria-controls="admin-site-menu">
    <i class="icon bi bi-globe2" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Nav Admin Site')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $adminSiteOpen ? 'show' : '' }}" id="admin-site-menu" data-bs-parent="#admin-navigation">
<li class="nav-item"><a class="nav-link {{ request()->is('admin/site*') ? 'active' : '' }}" href="{{ route('showSite') }}"><i class="icon bi bi-palette" aria-hidden="true"></i><span class="item-name">{{__('messages.Site Customization')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/pages*') ? 'active' : '' }}" href="{{ route('showSitePage') }}"><i class="icon bi bi-file-earmark-text" aria-hidden="true"></i><span class="item-name">{{__('messages.Footer Pages')}}</span></a></li>
  </ul>
</li>
    <li class="nav-item nav-accordion-group nav-accordion-admin">
  <a class="nav-link nav-accordion-toggle {{ $adminThemesOpen ? 'active' : '' }}" href="#admin-themes-menu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminThemesOpen ? 'true' : 'false' }}" aria-controls="admin-themes-menu">
    <i class="icon bi bi-brush" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Nav Admin Themes')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $adminThemesOpen ? 'show' : '' }}" id="admin-themes-menu" data-bs-parent="#admin-navigation">
<li class="nav-item"><a class="nav-link {{ request()->is('admin/theme') ? 'active' : '' }}" href="{{ route('showThemes') }}"><i class="icon bi bi-brush" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Theme Manager')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/github-themes*') ? 'active' : '' }}" href="{{ route('githubThemes') }}"><i class="icon bi bi-github" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin GitHub Themes')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/linktype*') ? 'active' : '' }}" href="{{ url('/admin/linktype') }}"><i class="icon bi bi-grid" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Block Types')}}</span></a></li>
  </ul>
</li>
    <li class="nav-item nav-accordion-group nav-accordion-admin">
  <a class="nav-link nav-accordion-toggle {{ $adminSystemOpen ? 'active' : '' }}" href="#admin-system-menu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $adminSystemOpen ? 'true' : 'false' }}" aria-controls="admin-system-menu">
    <i class="icon bi bi-sliders" aria-hidden="true"></i>
    <span class="item-name">{{__('messages.Nav Admin System')}}</span>
    <i class="right-icon bi bi-chevron-down" aria-hidden="true"></i>
  </a>
  <ul class="sub-nav collapse {{ $adminSystemOpen ? 'show' : '' }}" id="admin-system-menu" data-bs-parent="#admin-navigation">
<li class="nav-item"><a class="nav-link {{ request()->is('admin/config') ? 'active' : '' }}" href="{{ route('showConfig') }}"><i class="icon bi bi-sliders" aria-hidden="true"></i><span class="item-name">{{__('messages.Config')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/backups') ? 'active' : '' }}" href="{{ route('showBackups') }}"><i class="icon bi bi-database" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Backups')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/advanced-config') ? 'active' : '' }}" href="{{ route('showFileEditor') }}"><i class="icon bi bi-gear-wide-connected" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Advanced Config')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/env') ? 'active' : '' }}" href="{{ route('showEnvEditor') }}"><i class="icon bi bi-file-earmark-code" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Env')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('admin/phpinfo') ? 'active' : '' }}" href="{{ route('phpinfo') }}"><i class="icon bi bi-cpu" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin PHP Info')}}</span></a></li>
<li class="nav-item"><a class="nav-link {{ request()->is('update') ? 'active' : '' }}" href="{{ url('/update') }}"><i class="icon bi bi-arrow-repeat" aria-hidden="true"></i><span class="item-name">{{__('messages.Nav Admin Updates')}}</span></a></li>
  </ul>
</li>
  </ul>
</li>
@endif
</ul>
                <!-- Sidebar Menu End -->        </div>
        </div>
        <div class="sidebar-footer"></div>
    </aside>    <main class="main-content">
      <div class="position-relative iq-banner">
        <!--Nav Start-->
        <nav class="nav navbar navbar-expand-lg navbar-light iq-navbar mobile-unified-nav">
          <div class="container-fluid navbar-inner">
            <a href="{{ route('panelIndex') }}" class="navbar-brand">

                <!--Logo start-->
                <div class="logo-main">
                  @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
                  <div class="logo-normal">
                    <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;" alt="Logotipo LinkFree">
                </div>
                <div class="logo-mini">
                  <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;" alt="Logotipo LinkFree">
                </div>
                  @else
                  <div class="logo-normal">
                    <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/favicon.svg') }}" width="30px" height="30px" alt="Logotipo LinkFree">
                </div>
                <div class="logo-mini">
                  <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/favicon.svg') }}" width="30px" height="30px" alt="Logotipo LinkFree">
                </div>
                  @endif
                  </div>
                <!--logo End-->


                <h4 class="logo-title">{{env('APP_NAME')}}</h4>
            </a>
            <button type="button" class="sidebar-toggle" data-toggle="sidebar" data-active="true" aria-label="Recolher menu lateral" aria-expanded="true" data-label-expanded="Recolher menu lateral" data-label-collapsed="Expandir menu lateral" style="border:0;background:transparent;padding:0;">
                <i class="icon">
                 <svg  width="20px" class="icon-20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                </svg>
                </i>
            </button>
            {{-- <div class="input-group search-input">
              <span class="input-group-text" id="search-input">
                <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle>
                    <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </span>
              <input type="search" class="form-control" placeholder="Search...">
            </div> --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon">
                  <span class="mt-2 navbar-toggler-bar bar1"></span>
                  <span class="navbar-toggler-bar bar2"></span>
                  <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
                <li class="nav-item dropdown">
                  <a href="#"  class="nav-link" aria-label="Notificações" id="notification-drop" data-bs-toggle="dropdown" >
                      <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.7695 11.6453C19.039 10.7923 18.7071 10.0531 18.7071 8.79716V8.37013C18.7071 6.73354 18.3304 5.67907 17.5115 4.62459C16.2493 2.98699 14.1244 2 12.0442 2H11.9558C9.91935 2 7.86106 2.94167 6.577 4.5128C5.71333 5.58842 5.29293 6.68822 5.29293 8.37013V8.79716C5.29293 10.0531 4.98284 10.7923 4.23049 11.6453C3.67691 12.2738 3.5 13.0815 3.5 13.9557C3.5 14.8309 3.78723 15.6598 4.36367 16.3336C5.11602 17.1413 6.17846 17.6569 7.26375 17.7466C8.83505 17.9258 10.4063 17.9933 12.0005 17.9933C13.5937 17.9933 15.165 17.8805 16.7372 17.7466C17.8215 17.6569 18.884 17.1413 19.6363 16.3336C20.2118 15.6598 20.5 14.8309 20.5 13.9557C20.5 13.0815 20.3231 12.2738 19.7695 11.6453Z" fill="currentColor"></path>
                        <path opacity="0.4" d="M14.0088 19.2283C13.5088 19.1215 10.4627 19.1215 9.96275 19.2283C9.53539 19.327 9.07324 19.5566 9.07324 20.0602C9.09809 20.5406 9.37935 20.9646 9.76895 21.2335L9.76795 21.2345C10.2718 21.6273 10.8632 21.877 11.4824 21.9667C11.8123 22.012 12.1482 22.01 12.4901 21.9667C13.1083 21.877 13.6997 21.6273 14.2036 21.2345L14.2026 21.2335C14.5922 20.9646 14.8734 20.5406 14.8983 20.0602C14.8983 19.5566 14.4361 19.327 14.0088 19.2283Z" fill="currentColor"></path>
                        @if($GLOBALS['activenotify'])<circle cx="17" cy="17" r="5" fill="var(--lf-success-border)" stroke="var(--lf-surface)" stroke-width="2"/>@endif
                      </svg>
                      <span class="bg-danger dots"></span>
                  </a>
                  <div class="p-0 sub-drop dropdown-menu dropdown-menu-end notification-panel" aria-labelledby="notification-drop">
                      <div class="m-0 shadow-none card">
                        <div class="py-3 card-header d-flex justify-content-between bg-primary">
                            <div class="header-title">
                              <h5 class="mb-0 text-white">{{__('messages.All Notifications')}}</h5>
                            </div>
                        </div>
                        <div class="p-0 card-body">
                          @stack('notifications')
                        </div>
                      </div>
                  </div>
                </li>

                {{-- Atualizações administrativas acessíveis no menu Administração > Sistema. --}}

                <li class="nav-item dropdown">
                  <a class="py-0 nav-link d-flex align-items-center dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-target="#navbarDropdownIcon">
					@if(file_exists(base_path(findAvatar(Auth::user()->id))))
					<img src="{{ url(findAvatar(Auth::user()->id)) }}" alt="User-Profile" class="img-fluid avatar avatar-40 avatar-rounded" style="object-fit:cover;">
          @elseif(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
          <img src="{{ url("assets/linkstack/images/")."/".findFile('avatar') }}" alt="User-Profile" class="img logo" style="width:auto;height:30px;">
					@else
					<img src="{{ asset('assets/linkstack/images/favicon.svg') }}" alt="User-Profile" class="img-fluid avatar avatar-40 avatar-rounded">
					@endif
                    <div class="caption ms-3 d-none d-md-block ">
                        <h6 class="mb-0 caption-title">{{Auth::user()->name}}</h6>
                        <p class="mb-0 caption-sub-title">
                          @if(Auth::user()->role == "admin")
                          {{__('messages.Administrator')}}
                          @elseif(Auth::user()->role == "vip")
                          {{__('messages.Verified user')}}
                          @else
                          {{__('messages.User')}}
                          @endif
                        </p>
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="{{ url('/studio/page') }}"><i class="bi bi-person-fill"></i> {{__('messages.My Profile')}}</a></li>
                    <li><a class="dropdown-item" href="{{ url('/studio/profile') }}"><i class="bi bi-gear-fill"></i> {{__('messages.Settings')}}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
					<form action="{{ route('logout') }}" method="post">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
						<button type="submit" class="dropdown-item" href="{{ route('logout') }}"><i class="bi bi-box-arrow-in-left"></i> {{__('messages.Logout')}}</button>
					</form>
					</li>
                  </ul>
                </li>
              </ul>
            </div>
          </div>
        </nav>          <!-- Nav Header Component Start -->
        <style>.header-block{background-color:var(--bs-primary);border-bottom-left-radius:1rem;border-bottom-right-radius:1rem;}</style>
          <div class="iq-navbar-header header-block mb-2 welcome-slim" style="height:auto;">
        <style>
          .welcome-slim{position:relative}
          .welcome-slim .lf-welcome-summary{position:relative;display:flex;align-items:center;justify-content:center;min-height:58px;padding:0 48px}
          .welcome-slim .lf-welcome-copy{min-width:0;text-align:center}
          .welcome-slim .lf-welcome-toggle{
            position:absolute;inset:0;z-index:6;display:block;width:100%;height:100%;
            padding:0;border:0;border-radius:12px;background:transparent;color:#fff;
            cursor:pointer;text-align:right
          }
          .welcome-slim .lf-welcome-toggle:hover{background:rgba(255,255,255,.06)}
          .welcome-slim .lf-welcome-toggle:focus-visible{outline:3px solid #f6c65b;outline-offset:-2px}
          .welcome-slim .lf-welcome-toggle i{
            position:absolute;top:50%;right:2px;transform:translateY(-50%);
            display:grid;place-items:center;width:40px;height:40px;border-radius:11px;
            background:rgba(255,255,255,.17);border:1px solid rgba(255,255,255,.38);
            color:#fff;font-size:1.08rem
          }
          .welcome-slim .lf-welcome-actions{position:relative;z-index:10}
          .welcome-slim .lf-welcome-actions-inner{
            display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:9px;
            padding:15px 0 2px;margin-top:12px;border-top:1px solid rgba(255,255,255,.3)
          }
          .welcome-slim .lf-welcome-action{
            display:inline-flex;align-items:center;justify-content:center;gap:7px;
            box-sizing:border-box;min-height:42px;min-width:0;max-width:100%;
            border:1px solid rgba(255,255,255,.48);border-radius:10px;
            padding:9px 12px;background:rgba(255,255,255,.15);
            color:#fff!important;text-decoration:none;font-size:.85rem;font-weight:650;
            line-height:1.25;cursor:pointer
          }
          .welcome-slim .lf-welcome-action:hover{background:rgba(255,255,255,.27)}
          .welcome-slim .lf-welcome-action:focus-visible{outline:3px solid #f6c65b;outline-offset:2px}
          .welcome-slim .lf-welcome-share-dropdown{position:relative}
          .welcome-slim .lf-welcome-share-dropdown .dropdown-menu{max-width:min(320px,90vw);min-width:210px;z-index:1080}
          .welcome-slim .lf-welcome-share-dropdown .dropdown-item{white-space:normal;overflow-wrap:anywhere}
          @media(max-width:575px){
            .welcome-slim .lf-welcome-summary{padding:0 40px 0 0;min-height:56px}
            .welcome-slim .lf-welcome-copy{text-align:left}
            .welcome-slim .lf-welcome-toggle i{right:0;width:36px;height:36px}
            .welcome-slim .lf-welcome-actions-inner{display:grid;grid-template-columns:minmax(0,1fr);gap:8px}
            .welcome-slim .lf-welcome-share-dropdown{width:100%}
            .welcome-slim .lf-welcome-action{width:100%;justify-content:center}
          }
        </style>
        <div class="container-fluid iq-container">
          <div class="row">
            <div class="col-md-12">
              <div class="lf-welcome-summary">
                <div class="lf-welcome-copy">
                  @if(empty($usrhandl))
                    <h1>👋 {{__('messages.Hi')}}, {{__('messages.stranger')}}</h1>
                  @else
                    <h1>👋 {{__('messages.Hi')}}, {{'@'.$usrhandl}}</h1>
                  @endif
                  <h5>{{__('messages.welcome', ['appName' => config('app.name')])}}</h5>
                  @if(empty($usrhandl))
                    <a href="{{ url('/studio/page') }}" class="btn btn-link btn-soft-light" style="position:relative;z-index:9">
                      <i class="bi bi-at" aria-hidden="true"></i> {{__('messages.Set a handle')}}
                    </a>
                  @endif
                </div>
                @if(!empty($usrhandl))
                  <button class="lf-welcome-toggle" type="button" data-bs-toggle="collapse"
                          data-bs-target="#lfWelcomeActions" aria-controls="lfWelcomeActions"
                          aria-expanded="false" aria-label="{{__('messages.Share your profile:')}}">
                    <i class="bi bi-share-fill" aria-hidden="true"></i>
                  </button>
                @endif
              </div>
              @if(!empty($usrhandl))
                <div class="collapse lf-welcome-actions" id="lfWelcomeActions">
                  <div class="lf-welcome-actions-inner">
                    <a class="lf-welcome-action" href="{{ url('/@'.$usrhandl) }}" target="_blank" rel="noopener noreferrer">
                      <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> {{__('messages.View Page')}}
                    </a>
                    <div class="dropdown lf-welcome-share-dropdown">
                      <button class="lf-welcome-action dropdown-toggle" type="button" id="lfWelcomeShareMenu"
                              data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-share-fill" aria-hidden="true"></i> {{__('messages.Share your profile:')}}
                      </button>
                      <ul class="dropdown-menu" aria-labelledby="lfWelcomeShareMenu">
                        @if(env('SUPPORTED_DOMAINS') !== '' and env('SUPPORTED_DOMAINS') !== null)
                          @php $sDomains = explode(',', str_replace(' ', '', env('SUPPORTED_DOMAINS'))); @endphp
                          @foreach($sDomains as $myvar)
                            <li><button class="dropdown-item share-button" type="button"
                                        data-share="{{'https://'.$myvar.'/@'.$usrhandl}}">
                              <i class="bi bi-files" aria-hidden="true"></i> {{ $myvar }}
                            </button></li>
                          @endforeach
                        @else
                          <li><button class="dropdown-item share-button" type="button"
                                      data-share="{{url('/@'.$usrhandl)}}">
                            <i class="bi bi-files" aria-hidden="true"></i> {{ str_replace(['http://', 'https://'], '', url('')) }}
                          </button></li>
                        @endif
                      </ul>
                    </div>
                    <button class="lf-welcome-action" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                      <i class="bi bi-qr-code-scan" aria-hidden="true"></i> {{__('messages.QR Code')}}
                    </button>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
              <div style="z-index:0!important;" class="iq-header-img">
                @php if(file_exists(base_path("assets/dashboard-themes/header.png"))){$headerImage = asset('assets/dashboard-themes/header.png');}else{$headerImage = asset('assets/images/dashboard/top-header-overlay.png');} @endphp
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="img-fluid w-100 h-100 animated-scaleX">
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="theme-color-purple-img img-fluid w-100 h-100 animated-scaleX">
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="theme-color-blue-img img-fluid w-100 h-100 animated-scaleX">
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="theme-color-green-img img-fluid w-100 h-100 animated-scaleX">
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="theme-color-yellow-img img-fluid w-100 h-100 animated-scaleX">
                  <img src="{{$headerImage}}" draggable="false" alt="header" class="theme-color-pink-img img-fluid w-100 h-100 animated-scaleX">
              </div>
          </div>          <!-- Nav Header Component End -->
        <!--Nav End-->

		@yield('content')

      <!-- Footer Section Start -->
      <footer class="footer">
          <div class="footer-body">
              <ul class="left-panel list-inline mb-0 p-0">
                @if(env('DISPLAY_FOOTER') === true)
                  @if(env('DISPLAY_FOOTER_HOME') === true)<li class="list-inline-item"><a class="list-inline-item" href="@if(str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) === "" ){{ url('') }}@else{{ str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) }}@endif">{{footer('Home')}}</a></li>@endif
                  @if(env('DISPLAY_FOOTER_TERMS') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Terms')) }}">{{footer('Terms')}}</a></li>@endif
                  @if(env('DISPLAY_FOOTER_PRIVACY') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Privacy')) }}">{{footer('Privacy')}}</a></li>@endif
                  @if(env('DISPLAY_FOOTER_CONTACT') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Contact')) }}">{{footer('Contact')}}</a></li>@endif
                @endif
              </ul>
              <div class="right-panel">
                {{__('messages.Copyright')}} &copy; @php echo date('Y'); @endphp {{ config('app.name') }}
                @if(env('DISPLAY_CREDIT_FOOTER') === true)
                  <span class="">
                    - {{__('messages.Made with')}}
                      <svg class="icon-15" width="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M15.85 2.50065C16.481 2.50065 17.111 2.58965 17.71 2.79065C21.401 3.99065 22.731 8.04065 21.62 11.5806C20.99 13.3896 19.96 15.0406 18.611 16.3896C16.68 18.2596 14.561 19.9196 12.28 21.3496L12.03 21.5006L11.77 21.3396C9.48102 19.9196 7.35002 18.2596 5.40102 16.3796C4.06102 15.0306 3.03002 13.3896 2.39002 11.5806C1.26002 8.04065 2.59002 3.99065 6.32102 2.76965C6.61102 2.66965 6.91002 2.59965 7.21002 2.56065H7.33002C7.61102 2.51965 7.89002 2.50065 8.17002 2.50065H8.28002C8.91002 2.51965 9.52002 2.62965 10.111 2.83065H10.17C10.21 2.84965 10.24 2.87065 10.26 2.88965C10.481 2.96065 10.69 3.04065 10.89 3.15065L11.27 3.32065C11.3618 3.36962 11.4649 3.44445 11.554 3.50912C11.6104 3.55009 11.6612 3.58699 11.7 3.61065C11.7163 3.62028 11.7329 3.62996 11.7496 3.63972C11.8354 3.68977 11.9247 3.74191 12 3.79965C13.111 2.95065 14.46 2.49065 15.85 2.50065ZM18.51 9.70065C18.92 9.68965 19.27 9.36065 19.3 8.93965V8.82065C19.33 7.41965 18.481 6.15065 17.19 5.66065C16.78 5.51965 16.33 5.74065 16.18 6.16065C16.04 6.58065 16.26 7.04065 16.68 7.18965C17.321 7.42965 17.75 8.06065 17.75 8.75965V8.79065C17.731 9.01965 17.8 9.24065 17.94 9.41065C18.08 9.58065 18.29 9.67965 18.51 9.70065Z" fill="currentColor"></path>
                      </svg>
                  </span> {{__('messages.by')}} <a href="https://linkstack.org/" target="_blank">LinkStack</a>.
                @endif
              </div>
          </div>
      </footer>
      <!-- Footer Section End -->    </main>

    <!-- offcanvas start -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExample" data-bs-scroll="true" data-bs-backdrop="true" aria-labelledby="offcanvasExampleLabel">
      <div class="offcanvas-header">
        <div class="d-flex align-items-center">
          <h3 class="offcanvas-title me-3" id="offcanvasExampleLabel">{{__('messages.Panel Theme')}}</h3>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body data-scrollbar">
        <div class="row">
          <div class="col-lg-12">
             <h5 class="mb-3">{{__('messages.Scheme')}}</h5>
            <div class="d-grid gap-3 grid-cols-3 mb-4">
              <div class="btn btn-border" data-setting="color-mode" data-name="color" data-value="auto">
                  <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path fill="currentColor" d="M7,2V13H10V22L17,10H13L17,2H7Z" />
                  </svg>
                <span class="ms-2 "> {{__('messages.Auto')}} </span>
              </div>

               <div class="btn btn-border" data-setting="color-mode" data-name="color" data-value="dark">
                 <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path fill="currentColor" d="M9,2C7.95,2 6.95,2.16 6,2.46C10.06,3.73 13,7.5 13,12C13,16.5 10.06,20.27 6,21.54C6.95,21.84 7.95,22 9,22A10,10 0 0,0 19,12A10,10 0 0,0 9,2Z" />
                  </svg>
                <span class="ms-2 "> {{__('messages.Dark')}}  </span>
              </div>
               <div class="btn btn-border active" data-setting="color-mode" data-name="color" data-value="light">
                  <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8M12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18M20,8.69V4H15.31L12,0.69L8.69,4H4V8.69L0.69,12L4,15.31V20H8.69L12,23.31L15.31,20H20V15.31L23.31,12L20,8.69Z" />
                </svg>
                <span class="ms-2 "> {{__('messages.Light')}}</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <div class="d-flex align-items-center justify-content-between">
            <h5 class="mt-4 mb-3">{{__('messages.Color Customizer')}}</h5>
            <button class="btn btn-transparent p-0 border-0" data-value="theme-color-default" data-info="#079aa2" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Default">
              <svg class="icon-18" width="18"  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21.4799 12.2424C21.7557 12.2326 21.9886 12.4482 21.9852 12.7241C21.9595 14.8075 21.2975 16.8392 20.0799 18.5506C18.7652 20.3986 16.8748 21.7718 14.6964 22.4612C12.518 23.1505 10.1711 23.1183 8.01299 22.3694C5.85488 21.6205 4.00382 20.196 2.74167 18.3126C1.47952 16.4293 0.875433 14.1905 1.02139 11.937C1.16734 9.68346 2.05534 7.53876 3.55018 5.82945C5.04501 4.12014 7.06478 2.93987 9.30193 2.46835C11.5391 1.99683 13.8711 2.2599 15.9428 3.2175L16.7558 1.91838C16.9822 1.55679 17.5282 1.62643 17.6565 2.03324L18.8635 5.85986C18.945 6.11851 18.8055 6.39505 18.549 6.48314L14.6564 7.82007C14.2314 7.96603 13.8445 7.52091 14.0483 7.12042L14.6828 5.87345C13.1977 5.18699 11.526 4.9984 9.92231 5.33642C8.31859 5.67443 6.8707 6.52052 5.79911 7.74586C4.72753 8.97119 4.09095 10.5086 3.98633 12.1241C3.8817 13.7395 4.31474 15.3445 5.21953 16.6945C6.12431 18.0446 7.45126 19.0658 8.99832 19.6027C10.5454 20.1395 12.2278 20.1626 13.7894 19.6684C15.351 19.1743 16.7062 18.1899 17.6486 16.8652C18.4937 15.6773 18.9654 14.2742 19.0113 12.8307C19.0201 12.5545 19.2341 12.3223 19.5103 12.3125L21.4799 12.2424Z" fill="#31BAF1"/>
                <path d="M20.0941 18.5594C21.3117 16.848 21.9736 14.8163 21.9993 12.7329C22.0027 12.4569 21.7699 12.2413 21.4941 12.2512L19.5244 12.3213C19.2482 12.3311 19.0342 12.5633 19.0254 12.8395C18.9796 14.283 18.5078 15.6861 17.6628 16.8739C16.7203 18.1986 15.3651 19.183 13.8035 19.6772C12.2419 20.1714 10.5595 20.1483 9.01246 19.6114C7.4654 19.0746 6.13845 18.0534 5.23367 16.7033C4.66562 15.8557 4.28352 14.9076 4.10367 13.9196C4.00935 18.0934 6.49194 21.37 10.008 22.6416C10.697 22.8908 11.4336 22.9852 12.1652 22.9465C13.075 22.8983 13.8508 22.742 14.7105 22.4699C16.8889 21.7805 18.7794 20.4073 20.0941 18.5594Z" fill="#0169CA"/>
              </svg>
            </button>
            </div>
            <div class="grid-cols-5 mb-4 d-grid gap-x-2">
              <div class="btn btn-border bg-transparent"  data-value="theme-color-blue"   data-info="#573BFF" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Theme-1">
              <svg  class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" > <circle cx="12" cy="12" r="10" fill="#00C3F9" /> <path d="M2,12 a1,1 1 1,0 20,0" fill="#573BFF" /></svg>
              </div>
              <div class="btn btn-border bg-transparent" data-value="theme-color-gray" data-info="#FD8D00" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Theme-2">
              <svg  class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" > <circle cx="12" cy="12" r="10" fill="#91969E" /> <path d="M2,12 a1,1 1 1,0 20,0" fill="#FD8D00" /></svg>
              </div>
              <div class="btn btn-border bg-transparent"  data-value="theme-color-red" data-info="#366AF0" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Theme-3">
              <svg  class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" > <circle cx="12" cy="12" r="10" fill="#DB5363" /> <path d="M2,12 a1,1 1 1,0 20,0" fill="#366AF0" /></svg>
              </div>
              <div class="btn btn-border bg-transparent"  data-value="theme-color-yellow" data-info="#6410F1" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Theme-4">
              <svg  class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" > <circle cx="12" cy="12" r="10" fill="#EA6A12" /> <path d="M2,12 a1,1 1 1,0 20,0" fill="#6410F1" /></svg>
              </div>
              <div class="btn btn-border bg-transparent"  data-value="theme-color-pink" data-info="#25C799" data-setting="color-mode1" data-name="color" data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Theme-5">
              <svg  class="customizer-btn icon-32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" > <circle cx="12" cy="12" r="10" fill="#E586B3" /> <path d="M2,12 a1,1 1 1,0 20,0" fill="#25C799" /></svg>
              </div>

            </div>
            {{-- <hr class="hr-horizontal">
            <h5 class="mb-3 mt-4">Scheme Direction</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="text-center">
                <img src="{{asset('assets/images/settings/dark/01.png')}}" alt="ltr" class="mode dark-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="dir-mode" data-name="dir" data-value="ltr">
                <img src="{{asset('assets/images/settings/light/01.png')}}" alt="ltr" class="mode light-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="dir-mode" data-name="dir" data-value="ltr">
                <span class=" mt-2"> LTR </span>
              </div>
               <div class="text-center">
                 <img src="{{asset('assets/images/settings/dark/02.png')}}" alt="" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="dir-mode" data-name="dir" data-value="rtl">
                  <img src="{{asset('assets/images/settings/light/02.png')}}" alt="" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="dir-mode" data-name="dir" data-value="rtl">
                  <span class="mt-2 "> RTL  </span>
              </div>
            </div> --}}
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">{{__('messages.Sidebar Color')}}</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color" data-value="sidebar-white">
                <span class=""> {{__('messages.Default')}} </span>
              </div>
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color" data-value="sidebar-dark">
                <span class=""> {{__('messages.Dark')}} </span>
              </div>
              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color" data-value="sidebar-color">
                <span class=""> {{__('messages.Color')}} </span>
              </div>

              <div class="btn btn-border d-block" data-setting="sidebar" data-name="sidebar-color" data-value="sidebar-transparent">
                <span class=""> {{__('messages.Transparent')}} </span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">{{__('messages.Sidebar Types')}}</h5>
            <div class="d-grid gap-3 grid-cols-3 mb-4">
              <div class="text-center">
                <img src="{{asset('assets/images/settings/dark/03.png')}}" alt="mini" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-mini">
                <img src="{{asset('assets/images/settings/light/03.png')}}" alt="mini" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-mini">
                <span class="mt-2">{{__('messages.Mini')}}</span>
              </div>
              <div class="text-center">
               <img src="{{asset('assets/images/settings/dark/04.png')}}" alt="hover" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-hover" data-extra-value="sidebar-mini">
               <img src="{{asset('assets/images/settings/light/04.png')}}" alt="hover" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-hover" data-extra-value="sidebar-mini">
                <span class="mt-2">{{__('messages.Hover')}}</span>
              </div>
              <div class="text-center">
                 <img src="{{asset('assets/images/settings/dark/05.png')}}" alt="boxed" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-boxed">
                 <img src="{{asset('assets/images/settings/light/05.png')}}" alt="boxed" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-type" data-value="sidebar-boxed">
                <span class="mt-2">{{__('messages.Boxed')}}</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            <h5 class="mt-4 mb-3">{{__('messages.Sidebar Active Style')}}</h5>
            <div class="d-grid gap-3 grid-cols-2 mb-4">
              <div class="text-center">
                <img src="{{asset('assets/images/settings/dark/06.png')}}" alt="rounded-one-side" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-rounded">
                <img src="{{asset('assets/images/settings/light/06.png')}}" alt="rounded-one-side" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-rounded">
                <span class="mt-2">{{__('messages.Rounded One Side')}}</span>
              </div>
              <div class="text-center">
                <img src="{{asset('assets/images/settings/dark/07.png')}}" alt="rounded-all" class="mode dark-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-rounded-all">
                <img src="{{asset('assets/images/settings/light/07.png')}}" alt="rounded-all" class="mode light-img img-fluid btn-border p-0 flex-column active mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-rounded-all">
                <span class="mt-2">{{__('messages.Rounded All')}}</span>
              </div>
              <div class="text-center">
                 <img src="{{asset('assets/images/settings/dark/08.png')}}" alt="pill-one-side" class="mode dark-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-pill">
                 <img src="{{asset('assets/images/settings/light/09.png')}}" alt="pill-one-side" class="mode light-img img-fluid btn-border p-0 flex-column mb-2" data-setting="sidebar" data-name="sidebar-item" data-value="navs-pill">
                <span class="mt-2">{{__('messages.Pill One Side')}}</span>
              </div>
              <div class="text-center">
                 <img src="{{asset('assets/images/settings/dark/09.png')}}" alt="pill-all" class="mode dark-img img-fluid btn-border p-0 flex-column" data-setting="sidebar" data-name="sidebar-item" data-value="navs-pill-all">
                 <img src="{{asset('assets/images/settings/light/08.png')}}" alt="pill-all" class="mode light-img img-fluid btn-border p-0 flex-column" data-setting="sidebar" data-name="sidebar-item" data-value="navs-pill-all">
                <span class="mt-2">{{__('messages.Pill All')}}</span>
              </div>
            </div>
            <hr class="hr-horizontal">
            </div>
          </div>
        </div>
      </div>
    </div>

      <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="staticBackdropLabel">{{__('messages.Scan QR Code')}}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              @php
              try {
                $redirectURL = !empty(Auth::user()->littlelink_name) ? url('/@'.Auth::user()->littlelink_name) : url('/u/'.Auth::id());

                $argValues = config('advanced-config.qr_code_gradient') ?? [0, 0, 0, 0, 0, 0, 'diagonal'];
                list($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7) = $argValues;

                if (extension_loaded('imagick')) {
                  $imgSrc = QrCode::format('png')->gradient($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7)->eye('circle')->style('round')->size(1000)->generate($redirectURL);
                  $imgSrc = base64_encode($imgSrc);
                  $imgSrc = 'data:image/png;base64,' . $imgSrc;
                  $imgType = 'png';
                } else {
                  $imgSrc = QrCode::gradient($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7)->eye('circle')->style('round')->size(1000)->generate($redirectURL);
                  $imgSrc = base64_encode($imgSrc);
                  $imgSrc = 'data:image/svg+xml;base64,' . $imgSrc;
                  $imgType = 'svg';
                }

              } catch(exception $e) {
                $imgSrc = url('/assets/linkstack/images/themes/no-preview.png');
                $imgType = NULL;
              }
              @endphp
              <div class="modal-body">
                <div class="bd-example">
                  <img id="generatedImage" draggable="false" src="@php if(isset($imgSrc)){echo $imgSrc;} @endphp" style="width:100%;height:auto;" class="bd-placeholder-img img-thumbnail">
              </div>
              </div>
              <div class="modal-footer">
                @if($imgType == 'png')
                  <button type="button" class="btn btn-info" id="downloadButton">{{__('messages.Download')}}</button>
                @endif
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('messages.Close')}}</button>
              </div>
          </div>
      </div>
      </div>

      <script>
        document.addEventListener("DOMContentLoaded", function() {
            var downloadButton = document.getElementById("downloadButton");
            var generatedImage = document.getElementById("generatedImage");

            if (downloadButton && generatedImage) downloadButton.addEventListener("click", function() {
                var format = generatedImage.getAttribute("data-format") || "png";
                var downloadLink = document.createElement("a");
                downloadLink.href = generatedImage.src;
                downloadLink.download = "generated_image." + format;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            });
        });
        </script>

    <!-- Library Bundle Script -->
    <script src="{{asset('assets/js/core/libs.min.js')}}"></script>

    <!-- External Library Bundle Script -->
    <script src="{{asset('assets/js/core/external.min.js')}}"></script>

    <!-- Widgetchart Script -->
    <script src="{{asset('assets/js/charts/widgetcharts.js')}}"></script>

    <!-- mapchart Script -->
    <script src="{{asset('assets/js/charts/vectore-chart.js')}}"></script>
    <script src="{{asset('assets/js/charts/dashboard.js')}}" ></script>

    <!-- fslightbox Script -->
    <script src="{{asset('assets/js/plugins/fslightbox.js')}}"></script>

    <!-- Settings Script -->
    <script src="{{asset('assets/js/plugins/setting.js')}}"></script>

    <!-- Slider-tab Script -->
    <script src="{{asset('assets/js/plugins/slider-tabs.js')}}"></script>

    <!-- Form Wizard Script -->
    <script src="{{asset('assets/js/plugins/form-wizard.js')}}"></script>

    <!-- AOS Animation Plugin-->
    <script src="{{asset('assets/vendor/aos/dist/aos.js')}}"></script>

    <!-- App Script -->
    <script src="{{asset('assets/js/hope-ui.js')}}" defer></script>
    <script src="{{ asset('assets/js/sidebar-auto-close.js') }}?v={{ filemtime(base_path('assets/js/sidebar-auto-close.js')) }}" defer></script>

    <!-- Flatpickr Script -->
    <script src="{{asset('assets/vendor/flatpickr/dist/flatpickr.min.js')}}"></script>
    <script src="{{asset('assets/js/plugins/flatpickr.js')}}" defer></script>

    <script src="{{asset('assets/js/plugins/prism.mini.js')}}"></script>

    <!-- Share Button -->
    <script>
      // Get a reference to all buttons with the class "share-button"
      const shareButtons = document.querySelectorAll('.share-button');

      // Add a click event listener to each button
      shareButtons.forEach(button => {
        button.addEventListener('click', () => {
          // Get the value to share/copy from the "data-share" attribute
          const valueToShare = button.dataset.share;

          // Check if the Web Share API is supported
          if (navigator.share) {
            // Call the Web Share API to open the native share dialog
            navigator.share({
              title: '{{__("messages.Share your profile")}}',
              text: valueToShare,
              url: valueToShare,
            })
            .catch(err => console.error('{{__("messages.Error sharing:")}}', err));
          } else {
            // If the Web Share API is not supported, copy the value to the clipboard
            navigator.clipboard.writeText(valueToShare)
            .then(() => {
              // If copying was successful, alert the user
              alert('{{__("messages.Text copied to clipboard!")}}');
            })
            .catch(err => {
              // If copying failed, alert the user
              alert('{{__("messages.Error copying text:")}}', err);
            });
          }
        });
      });
      </script>

<script src="{{ asset('assets/js/popper.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/Sortable.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery-block-ui.js') }}"></script>
<script src="{{ asset('assets/js/main-dashboard.js') }}?v={{ filemtime(base_path('assets/js/main-dashboard.js')) }}"></script>

@stack('sidebar-scripts')

  <script src="{{ asset('assets/js/accessibility.js') }}" defer></script>
</body>
</html>
