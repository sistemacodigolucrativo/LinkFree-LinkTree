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

<x-auth-card>
  <x-slot name="logo"></x-slot>
  <section class="lf-login-shell" aria-labelledby="lf-login-title">
    <aside class="lf-login-visual" aria-hidden="true">
      <div class="lf-login-visual-mark"><i class="bi bi-link-45deg"></i> {{ config('app.name') }}</div>
      <div class="lf-login-visual-center">
        <div class="lf-login-visual-symbol"><i class="bi bi-grid-1x2-fill"></i></div>
        <p class="lf-login-eyebrow">LINKFREE</p>
        <h2>{{ config('app.name') }}</h2>
        <p>{{ __('messages.Login to stay connected') }}</p>
      </div>
      <div class="lf-login-visual-footer"><span></span><span></span><span></span></div>
    </aside>
    <div class="lf-login-panel">
      <a href="{{ url('') }}" class="lf-login-brand d-flex align-items-center">
                <!--Logo start-->
                <div class="logo-main">
                    @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
                    <div class="logo-normal">
                      <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;">
                  </div>
                  <div class="logo-mini">
                    <img class="img logo" src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" style="width:auto;height:30px;">
                  </div>
                    @else
                    <div class="logo-normal">
                      <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/logo.svg') }}" width="30px" height="30px">
                  </div>
                  <div class="logo-mini">
                    <img class="img logo" type="image/svg+xml" src="{{ asset('assets/linkstack/images/logo.svg') }}" width="30px" height="30px">
                  </div>
                    @endif
                    </div>
                    <!--logo End-->
                <h4 class="logo-title ms-3">{{env('APP_NAME')}}</h4>
              </a>
      <div class="lf-login-heading">
        <p class="lf-login-kicker">{{ config('app.name') }}</p>
        <h1 id="lf-login-title">{{ __('messages.Sign In') }}</h1>
        <p>{{ __('messages.Login to stay connected') }}.</p>
      </div>
      <x-auth-session-status class="mb-4" :status="session('status')" />
      <x-auth-validation-errors class="mb-4" :errors="$errors" />
      <form class="lf-login-form" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <label for="email" class="form-label">{{__('messages.Email')}}</label>
                      <input type="email" class="form-control" id="email" name="email" autocomplete="username" inputmode="email" autocapitalize="none" aria-describedby="email" placeholder=" " value="{{ old('email') }}" required autofocus >
                    </div>
                  </div>
                  <div class="col-lg-12">
                    <div class="form-group">
                      <label for="password" class="form-label">{{__('messages.Password')}}</label>
                      <input type="password" class="form-control" id="password" aria-describedby="password" placeholder=" " name="password" required autocomplete="current-password" />
                    </div>
                  </div>
                  <div class="col-lg-12 d-flex justify-content-between lf-login-remember-row">
                    <div class="form-check mb-3">
                      <input type="checkbox" class="form-check-input" name="remember" id="remember_me">
                      <label class="form-check-label" for="remember_me">{{__('messages.Remember Me')}}</label>
                    </div>
                    <a href="{{ route('password.request') }}">{{__('messages.Forgot Password?')}}</a>
                  </div>
                </div>
                <div class="d-flex justify-content-center">
                  <button type="submit" class="btn btn-primary lf-login-submit">{{__('messages.Sign In')}}</button>
                </div>
                @if(env('ENABLE_SOCIAL_LOGIN') == 'true')
                <p class="text-center my-3">{{__('messages.or sign in with other accounts?')}}</p>
                <div class="d-flex justify-content-center">
                  <ul class="list-group list-group-horizontal list-group-flush lf-login-social">
                    @if(!empty(env('FACEBOOK_CLIENT_ID')))
                    <li class="list-group-item border-0 pb-0">
                      <a href="{{ route('social.redirect','facebook') }}" aria-label="Entrar com Facebook">
                        <i class="bi bi-facebook" aria-hidden="true"></i>
                      </a>
                    </li>
                    @endif
                    @if(!empty(env('TWITTER_CLIENT_ID')))
                    <li class="list-group-item border-0 pb-0">
                      <a href="{{ route('social.redirect','twitter') }}" aria-label="Entrar com Twitter">
                        <i class="bi bi-twitter" aria-hidden="true"></i>
                      </a>
                    </li>
                    @endif
                    @if(!empty(env('GOOGLE_CLIENT_ID')))
                    <li class="list-group-item border-0 pb-0">
                      <a href="{{ route('social.redirect','google') }}" aria-label="Entrar com Google">
                        <i class="bi bi-google" aria-hidden="true"></i>
                      </a>
                    </li>
                    @endif
                    @if(!empty(env('GITHUB_CLIENT_ID')))
                    <li class="list-group-item border-0 pb-0">
                      <a href="{{ route('social.redirect','github') }}" aria-label="Entrar com GitHub">
                        <i class="bi bi-github" aria-hidden="true"></i>
                      </a>
                    </li>
                    @endif
                  </ul>
                </div>
                @else
                <br>
                @endif
                @if ((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))
                <p class="mt-3 text-center">
                  {{__('messages.Don’t have an account?')}} <a href="{{ route('register') }}" class="text-underline">{{__('messages.Click here to sign up')}}.</a>
                </p>
                @endif
              </form>
    </div>
  </section>
</x-auth-card>
</x-guest-layout>
