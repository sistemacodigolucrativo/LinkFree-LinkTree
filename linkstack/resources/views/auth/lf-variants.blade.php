{{-- Modos adicionais do mesmo cartao de autenticacao. O backend e as rotas permanecem inalterados. --}}
<div class="lf-auth-view" data-lf-auth-view="forgot" @if($initialAuthView !== 'forgot') hidden @endif>
  <header class="lf-login-heading">
    <span class="lf-login-kicker">{{ config('app.name') }}</span>
    <h1>{{ __('messages.Forgot your password?') }}</h1>
    <p>{{ __('messages.No problem') }}</p>
  </header>
  <x-auth-session-status class="mb-3" :status="session('status')" />
  <x-auth-validation-errors class="mb-3" :errors="$errors" />
  <form class="lf-login-form" method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="lf-login-field">
      <label class="form-label" for="lf-forgot-email">{{ __('messages.Email') }}</label>
      <input type="email" class="form-control" id="lf-forgot-email" name="email"
             autocomplete="email" inputmode="email" value="{{ old('email') }}"
             autocapitalize="none" required>
    </div>
    <button type="submit" class="btn btn-primary lf-login-submit">{{ __('messages.Email Password Reset Link') }}</button>
    <p class="lf-login-register">
      <a data-lf-auth-target="login" href="{{ route('login') }}">{{ __('messages.Click here to sign in') }}</a>
    </p>
  </form>
</div>

@if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))
<div class="lf-auth-view" data-lf-auth-view="register" @if($initialAuthView !== 'register') hidden @endif>
  <header class="lf-login-heading">
    <span class="lf-login-kicker">{{ config('app.name') }}</span>
    <h1>{{ __('messages.Sign Up') }}</h1>
    <p>{{ str_starts_with(strtolower(app()->getLocale()), 'pt') ? 'Crie sua página pública, organize seus canais e compartilhe um endereço único.' : __('messages.Register to stay connected') }}</p>
  </header>
  <x-auth-session-status class="mb-3" :status="session('status')" />
  <x-auth-validation-errors class="mb-3" :errors="$errors" />
  <form class="lf-login-form" method="POST" action="{{ route('register') }}">
    @csrf
    <div class="lf-login-field">
      <label for="lf-register-name" class="form-label">{{ __('messages.Display Name') }}</label>
      <input type="text" class="form-control" id="lf-register-name" name="name"
             value="{{ old('name') }}" autocomplete="name" required>
    </div>
    <div class="lf-login-field">
      <label for="littlelink_name" class="form-label">{{ __('messages.Page URL') }}</label>
      <div class="input-group has-validation lf-login-handle">
        <span class="input-group-text">{{ str_replace(['http://', 'https://'], '', url('')) }}/@</span>
        <input type="text" class="form-control" id="littlelink_name" name="littlelink_name"
               value="{{ old('littlelink_name') }}" autocomplete="off" required>
      </div>
    </div>
    <div class="lf-login-field">
      <label for="lf-register-email" class="form-label">{{ __('messages.Email') }}</label>
      <input type="email" class="form-control" id="lf-register-email" name="email"
             value="{{ old('email') }}" autocomplete="email" inputmode="email" required>
    </div>
    <div class="lf-login-field">
      <label for="lf-register-password" class="form-label">{{ __('messages.Password') }}</label>
      <input type="password" class="form-control" id="lf-register-password" name="password"
             autocomplete="new-password" required>
    </div>
    <div class="lf-login-remember-row">
      <label class="lf-login-remember" for="lf-register-remember">
        <input type="checkbox" class="form-check-input" name="remember" id="lf-register-remember">
        <span>{{ __('messages.Remember Me') }}</span>
      </label>
    </div>
    <div class="lf-login-field">
      <label class="d-flex align-items-start gap-2" for="lf-accept-terms" style="font-size:.86rem;line-height:1.5">
        <input type="checkbox" class="form-check-input mt-1" id="lf-accept-terms" name="accept_terms"
          value="1" required @checked(old('accept_terms') == 1)>
        <span>
          {{ __('messages.lf_accept_intro') }}
          <a href="{{ route('pagesTerms') }}" target="_blank" rel="noopener noreferrer">{{ __('messages.lf_terms') }}</a>
          {{ __('messages.lf_and') }}
          <a href="{{ route('pagesPrivacy') }}" target="_blank" rel="noopener noreferrer">{{ __('messages.lf_privacy') }}</a>.
        </span>
      </label>
    </div>
    <button id="submit-btn" type="submit" class="btn btn-primary lf-login-submit">{{ __('messages.Sign Up') }}</button>
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
    <p class="lf-login-register">
      {{ __('messages.Already have an account?') }}
      <a data-lf-auth-target="login" href="{{ route('login') }}">{{ __('messages.Click here to sign in') }}</a>
    </p>
  </form>
  @include('auth.url-validation')
  <script>
  (function(){
    // Novo cadastro social: o aceite explicitamente marcado e enviado por POST com CSRF.
    document.addEventListener('click', async function(event){
      const link=event.target.closest('[data-lf-auth-view="register"] .lf-login-social a');
      if(!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      const checkbox=document.getElementById('lf-accept-terms');
      if(!checkbox || !checkbox.checked){if(checkbox)checkbox.reportValidity();return;}
      const match=new URL(link.href).pathname.match(/social-auth\/([^/]+)$/);
      if(!match)return;
      try{
        const url=@json(url('/social-auth'))+'/'+encodeURIComponent(match[1])+'/consent';
        const result=await fetch(url,{method:'POST',credentials:'same-origin',
          headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},
          body:JSON.stringify({accept_terms:1})});
        if(!result.ok)throw new Error('Consent failed');
        window.location.assign((await result.json()).redirect);
      }catch(error){alert(@json(__('messages.lf_accept_required')));}
    });
  })();
  </script>
</div>
@endif

@if($initialAuthView === 'reset')
<div class="lf-auth-view" data-lf-auth-view="reset">
  <header class="lf-login-heading">
    <span class="lf-login-kicker">{{ config('app.name') }}</span>
    <h1>{{ __('messages.Reset Password') }}</h1>
    <p>{{ __('messages.Enter a new password') }}</p>
  </header>
  <x-auth-validation-errors class="mb-3" :errors="$errors" />
  <form class="lf-login-form" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ request()->route('token') }}">
    <div class="lf-login-field">
      <label for="lf-reset-email" class="form-label">{{ __('messages.Email') }}</label>
      <input type="email" class="form-control" id="lf-reset-email" name="email"
             value="{{ old('email', request()->query('email')) }}" autocomplete="email" required>
    </div>
    <div class="lf-login-field">
      <label for="lf-reset-password" class="form-label">{{ __('messages.Password') }}</label>
      <input type="password" class="form-control" id="lf-reset-password" name="password"
             autocomplete="new-password" required>
    </div>
    <div class="lf-login-field">
      <label for="lf-reset-confirm" class="form-label">{{ __('messages.Confirm Password') }}</label>
      <input type="password" class="form-control" id="lf-reset-confirm" name="password_confirmation"
             autocomplete="new-password" required>
    </div>
    <button type="submit" class="btn btn-primary lf-login-submit">{{ __('messages.Reset Password') }}</button>
    <p class="lf-login-register">
      <a href="{{ route('login') }}">{{ __('messages.Click here to sign in') }}</a>
    </p>
  </form>
</div>
@endif
