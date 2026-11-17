<?php use App\Models\UserData; ?>

@php
    $ShowShrBtn = config('advanced-config.display_share_button');

    if ($ShowShrBtn === 'false') {
        $ShowShrBtn = 'false';
    } elseif ($ShowShrBtn === 'user') {
        $ShowShrBtn = Auth::check() && filled(Auth::user()->littlelink_name) ? 'true' : 'false';
    } elseif (UserData::getData($userinfo->id, 'disable-sharebtn') == "true") {
        $ShowShrBtn = 'false';
    } else {
        $ShowShrBtn = 'true';
    }

@endphp

{{-- O botão Compartilhar é uma sobreposição independente, não uma linha do
     documento. Os CSS de tema deixam .sharediv relativa (top:30px +
     padding-bottom:40px), criando a faixa vazia sobre o perfil público.
     Corrigir aqui alcança temas instalados por ZIP sem editá-los. --}}
<style id="linkfree-public-share-overlay">
  body > .sharediv {
    position: fixed !important;
    top: calc(14px + env(safe-area-inset-top, 0px)) !important;
    right: calc(14px + env(safe-area-inset-right, 0px)) !important;
    bottom: auto !important;
    left: auto !important;
    z-index: 100000 !important;
    display: block !important;
    width: max-content !important;
    max-width: calc(100vw - 28px) !important;
    height: auto !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
    float: none !important;
    pointer-events: none;
  }
  body > .sharediv > div { pointer-events: auto; }
  body > .sharediv .share-button {
    position: relative;
    max-width: 100%;
    pointer-events: auto;
  }
  @media (max-width: 575.98px) {
    body > .sharediv {
      top: calc(12px + env(safe-area-inset-top, 0px)) !important;
      right: calc(12px + env(safe-area-inset-right, 0px)) !important;
      max-width: calc(100vw - 24px) !important;
    }
  }
</style>

<div align="right" @if($ShowShrBtn == 'false') style="visibility:hidden" @endif class="sharediv">
  <div>
    <button type="button" style="border:0;font:inherit;cursor:pointer" class="sharebutton button-hover icon-hover share-button" data-share="{{url()->current()}}" tabindex="0" role="button" aria-label="{{__('messages.Share this page')}}">
      <i style="color: black;" class="fa-solid fa-share sharebutton-img share-icon hvr-icon"></i>
      <span class="sharebutton-mb">{{__('messages.Share')}}</span>
    </button>
  </div>
</div>
<span class="copy-icon" aria-hidden="true"></span>

@if($ShowShrBtn == 'true')
<script>const shareButtons=document.querySelectorAll(".share-button");shareButtons.forEach((e=>{e.addEventListener("click",(()=>{const r=e.dataset.share;navigator.share?navigator.share({title:"{{__('messages.Share this page')}}",url:r}).catch((e=>console.error("Error:",e))):navigator.clipboard.writeText(r).then((()=>{alert("{{__('messages.URL has been copied to your clipboard!')}}")})).catch((e=>{alert("Error",e)}))}))}));</script>
@endif
