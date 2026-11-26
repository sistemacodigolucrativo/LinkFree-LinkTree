<?php
$pages = DB::table('pages')->get();
foreach($pages as $page)
{
	//Gets value from database
}
?>

<x-guest-layout>
@include('layouts.lang')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-login.css') }}?v={{ filemtime(base_path('assets/css/linkfree-login.css')) }}">

@php
  $initialAuthView = in_array($initialAuthView ?? 'login', ['login', 'forgot', 'register', 'reset'], true)
    ? ($initialAuthView ?? 'login') : 'login';
@endphp
<x-auth-card>
  <x-slot name="logo"></x-slot>
  <section class="lf-login-shell" data-lf-auth-initial="{{ $initialAuthView }}" aria-label="{{ config('app.name') }}">
    <aside class="lf-login-visual" aria-hidden="true">
      <div class="lf-login-visual-brand">
        <span class="lf-login-visual-brand-icon"><i class="bi bi-link-45deg"></i></span>
        <span>{{ config('app.name') }}</span>
      </div>
      <div class="lf-login-visual-content">
        <span class="lf-login-visual-emblem"><i class="bi bi-grid-1x2-fill"></i></span>
        <p class="lf-login-visual-eyebrow">LINKFREE</p>
        <h2>{{ config('app.name') }}</h2>
        <p>{{ str_starts_with(strtolower(app()->getLocale()), 'pt') ? 'Reúna sua página, seus links e suas conexões em um só lugar.' : __('messages.Login to stay connected') }}</p>
      </div>
      <div class="lf-login-visual-accent"><span></span><span></span><span></span></div>
    </aside>

    <div class="lf-login-panel">
      <a href="{{ url('') }}" class="lf-login-brand">
        @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
          <img src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" alt="" class="lf-login-logo">
        @else
          <img src="{{ asset('assets/linkstack/images/favicon.svg') }}" alt="" class="lf-login-logo">
        @endif
        <span class="lf-login-brand-name">{{ config('app.name') }}</span>
      </a>

      <div class="lf-auth-view" data-lf-auth-view="login" @if($initialAuthView !== 'login') hidden @endif>
      <header class="lf-login-heading">
        <span class="lf-login-kicker">{{ config('app.name') }}</span>
        <h1 id="lf-login-title">{{ __('messages.Sign In') }}</h1>
        <p>{{ str_starts_with(strtolower(app()->getLocale()), 'pt') ? 'Entre para organizar blocos, personalizar sua página e acompanhar cliques.' : __('messages.Login to stay connected') }}</p>
      </header>

      <x-auth-session-status class="mb-3" :status="session('status')" />
      <x-auth-validation-errors class="mb-3" :errors="$errors" />

      <form class="lf-login-form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="lf-login-field">
          <label for="email" class="form-label">{{ __('messages.Email') }}</label>
          <input type="email" class="form-control" id="email" name="email"
                 value="{{ old('email') }}" autocomplete="username" inputmode="email"
                 autocapitalize="none" placeholder="nome@exemplo.com" required autofocus>
        </div>

        <div class="lf-login-field">
          <label for="password" class="form-label">{{ __('messages.Password') }}</label>
          <input type="password" class="form-control" id="password" name="password"
                 autocomplete="current-password" required>
        </div>

        <div class="lf-login-remember-row">
          <label class="lf-login-remember" for="remember_me">
            <input type="checkbox" class="form-check-input" name="remember" id="remember_me">
            <span>{{ __('messages.Remember Me') }}</span>
          </label>
          <a data-lf-auth-target="forgot" href="{{ route('password.request') }}">{{ __('messages.Forgot Password?') }}</a>
        </div>

        <button type="submit" class="btn btn-primary lf-login-submit">{{ __('messages.Sign In') }}</button>

        @if(env('ENABLE_SOCIAL_LOGIN') == 'true')
          <div class="lf-login-divider"><span>{{ __('messages.or sign in with other accounts?') }}</span></div>
          <div class="lf-login-social">
            @if(!empty(env('FACEBOOK_CLIENT_ID')))
              <a href="{{ route('social.redirect','facebook') }}" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
            @endif
            @if(!empty(env('TWITTER_CLIENT_ID')))
              <a href="{{ route('social.redirect','twitter') }}" aria-label="Twitter"><i class="bi bi-twitter" aria-hidden="true"></i></a>
            @endif
            @if(!empty(env('GOOGLE_CLIENT_ID')))
              <a href="{{ route('social.redirect','google') }}" aria-label="Google"><i class="bi bi-google" aria-hidden="true"></i></a>
            @endif
            @if(!empty(env('GITHUB_CLIENT_ID')))
              <a href="{{ route('social.redirect','github') }}" aria-label="GitHub"><i class="bi bi-github" aria-hidden="true"></i></a>
            @endif
          </div>
        @endif

        @if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))
          <p class="lf-login-register">
            {{ __('messages.Don’t have an account?') }}
            <a data-lf-auth-target="register" href="{{ route('register') }}">{{ __('messages.Click here to sign up') }}</a>
          </p>
        @endif
      </form>
      </div>
      @include('auth.lf-variants')
    </div>
  </section>
  <script src="{{ asset('assets/js/linkfree-auth.js') }}?v={{ filemtime(base_path('assets/js/linkfree-auth.js')) }}" defer></script>
</x-auth-card>
</x-guest-layout>
