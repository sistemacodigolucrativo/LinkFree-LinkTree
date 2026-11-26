@php
  // Match the same brand/skeleton resources used by the member's public page.
  $lfPreviewTheme = (string) (auth()->user()->theme ?? '');
  $lfPreviewThemeValid = $lfPreviewTheme !== '' && $lfPreviewTheme !== 'default'
    && preg_match('/\A[A-Za-z0-9_-]+\z/', $lfPreviewTheme);
  $lfPreviewThemeDir = $lfPreviewThemeValid ? base_path('themes/'.$lfPreviewTheme) : null;
  $lfPreviewThemeConfig = $lfPreviewThemeDir && is_file($lfPreviewThemeDir.'/config.php')
    ? (include $lfPreviewThemeDir.'/config.php') : [];
  $lfPreviewThemeConfig = is_array($lfPreviewThemeConfig) ? $lfPreviewThemeConfig : [];
  $lfPreviewBrands = $lfPreviewThemeDir && ($lfPreviewThemeConfig['use_default_buttons'] ?? '') !== 'true'
    && is_file($lfPreviewThemeDir.'/brands.css')
    ? asset('themes/'.$lfPreviewTheme.'/brands.css')
    : asset('assets/linkstack/css/brands.css');
  $lfPreviewSkeleton = $lfPreviewThemeDir && is_file($lfPreviewThemeDir.'/skeleton-auto.css')
    ? asset('themes/'.$lfPreviewTheme.'/skeleton-auto.css')
    : asset('assets/linkstack/css/skeleton-auto.css');
  $lfPreviewCustomIcons = $lfPreviewThemeDir && ($lfPreviewThemeConfig['use_custom_icons'] ?? '') === 'true';
  $lfPreviewIconExtension = (string) ($lfPreviewThemeConfig['custom_icon_extension'] ?? '.svg');
  if (!preg_match('/\A\.(svg|png|webp|jpg|jpeg)\z/i', $lfPreviewIconExtension)) {
    $lfPreviewIconExtension = '.svg';
  }
@endphp
<label for="predefined-button" class="form-label">{{ __('member_ui.button_icon') }}</label>
<?php use App\Models\Button; $button = Button::find($button_id); if(isset($button->name)){$buttonName = $button->name;}else{$buttonName = 0;} ?>

<div class="lf-platform-picker"
     data-brand-css="{{ $lfPreviewBrands }}"
     data-skeleton-css="{{ $lfPreviewSkeleton }}"
     data-icon-base="{{ asset('assets/linkstack/icons') }}"
     data-custom-icon-base="{{ $lfPreviewCustomIcons ? asset('themes/'.$lfPreviewTheme.'/extra/custom-icons') : '' }}"
     data-custom-icon-extension="{{ $lfPreviewIconExtension }}">
  <select id="predefined-button" name="button" class="form-control" required>
    <option value="" disabled {{ $buttonName === 0 ? 'selected' : '' }}>Selecione uma plataforma</option>
    @if($buttonName !== 0)
      <option value="{{ $buttonName }}" selected>{{ ucfirst($buttonName) }}</option>
    @endif
    @foreach ($buttons as $b)
      @if(!$b['exclude'] && $b['name'] !== $buttonName)
        <option class="button button-{{ $b['name'] }}" value="{{ $b['name'] }}">{{ $b['title'] }}</option>
      @endif
    @endforeach
  </select>
</div>

<label for='field-title' class='form-label'>{{__('messages.Custom Title')}}</label>
<input type='text' name='title' value='{{$title}}' class='form-control' id="field-title" />
<span class='small text-muted'>{{__('messages.Leave blank for default title')}}</span><br>

<label for="field-link" class='form-label'>{{__('messages.URL')}}</label>
<input type='url' name='link' value='{{$link}}' class='form-control' required id="field-link" />
<span class='small text-muted'>{{__('messages.Enter the link URL')}}</span>

<div id="lf-whatsapp-fields" class="border rounded-3 p-3 mt-3" hidden aria-live="polite">
  <div class="row g-2">
    <div class="col-sm-4">
      <label class="form-label" for="lf-wa-country">{{ __('messages.lf_wa_country') }}</label>
      <input class="form-control" id="lf-wa-country" name="wa_country" type="tel" inputmode="tel"
        autocomplete="tel-country-code" value="{{ old('wa_country', '55') }}" maxlength="4">
    </div>
    <div class="col-sm-8">
      <label class="form-label" for="lf-wa-phone">{{ __('messages.lf_wa_phone') }}</label>
      <input class="form-control" id="lf-wa-phone" name="wa_phone" type="tel" inputmode="tel"
        autocomplete="tel-national" value="{{ old('wa_phone', '') }}" maxlength="24">
    </div>
    <div class="col-12">
      <label class="form-label" for="lf-wa-message">{{ __('messages.lf_wa_message') }}</label>
      <textarea class="form-control" id="lf-wa-message" name="wa_message" rows="3" maxlength="1000">{{ old('wa_message', '') }}</textarea>
    </div>
    <div class="col-12">
      <a class="btn btn-success" id="lf-wa-preview" target="_blank" rel="noopener noreferrer"
         href="#" aria-disabled="true"><i class="bi bi-whatsapp" aria-hidden="true"></i> {{ __('messages.lf_wa_preview') }}</a>
      <p class="form-text" id="lf-wa-warning" role="status"></p>
    </div>
  </div>
</div>
<script>
(function(){
  const selected = document.getElementById('predefined-button');
  const wrap = document.getElementById('lf-whatsapp-fields');
  const link = document.getElementById('field-link');
  if(!selected || !wrap || !link) return;
  const country = document.getElementById('lf-wa-country');
  const phone = document.getElementById('lf-wa-phone');
  const message = document.getElementById('lf-wa-message');
  const preview = document.getElementById('lf-wa-preview');
  const warning = document.getElementById('lf-wa-warning');
  if(/^https:\/\/wa\.me\/\d+/i.test(link.value) && !phone.value) {
    try {
      const existing = new URL(link.value);
      const digits = existing.pathname.replace(/\D/g,'');
      if(digits.startsWith('55') && digits.length >= 12){country.value='55';phone.value=digits.slice(2);}
      else {phone.value=digits;country.value='';}
      message.value = existing.searchParams.get('text')||'';
    } catch(e){}
  }
  function refresh(){
    const active=selected.value==='whatsapp';
    wrap.hidden=!active;
    link.readOnly=active;
    country.required=phone.required=active;
    const c=country.value.replace(/\D/g,'');
    const n=phone.value.replace(/\D/g,'');
    const valid=/^\+?[0-9]{1,3}$/.test(country.value.trim()) && /^[0-9() .+-]{8,24}$/.test(phone.value.trim()) && (c+n).length>=8 && (c+n).length<=15;
    if(active){
      const url=valid?'https://wa.me/'+c+n+(message.value?'?text='+encodeURIComponent(message.value):''):'';
      link.value=url;
      preview.href=url||'#';
      preview.setAttribute('aria-disabled', String(!valid));
      preview.style.pointerEvents=valid?'':'none';
      warning.textContent=valid?'':@json(__('messages.lf_wa_invalid_phone'));
    }
  }
  selected.addEventListener('change',refresh);
  [country,phone,message].forEach(el=>el.addEventListener('input',refresh));
  refresh();
})();
</script>
