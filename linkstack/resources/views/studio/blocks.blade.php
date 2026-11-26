@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
<style>
/* Cards de seleção continuam links; a prévia é um controle independente. */
.lf-blocks-page .lf-block-grid{
 display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));
 gap:14px;align-items:start
}
.lf-blocks-page .lf-block-card{
 display:flex;flex-direction:column;min-width:0;max-width:100%;
 box-sizing:border-box;border-radius:14px;overflow:hidden;
 background:var(--lf-raised,#f5f7fc);border:1px solid var(--lf-border,#75839a);
 color:var(--lf-text,#18243b);
 transition:background-color .2s,border-color .2s
}
.lf-blocks-page .lf-block-card:hover{background:var(--lf-hover,#eaf0fa);border-color:var(--lf-link,#204a9b)}
.lf-blocks-page .lf-block-choice{
 display:flex;align-items:flex-start;gap:14px;width:100%;min-width:0;
 box-sizing:border-box;min-height:103px;padding:19px 19px 11px;
 color:var(--lf-text,#18243b);text-decoration:none;overflow-wrap:anywhere
}
.lf-blocks-page .lf-block-choice:hover{color:var(--lf-text,#18243b)}
.lf-blocks-page .lf-block-choice:focus-visible,.lf-blocks-page .lf-block-preview-toggle:focus-visible{
 outline:3px solid var(--lf-focus,#275fbd);outline-offset:-3px;border-radius:9px
}
.lf-blocks-page .lf-block-icon{
 display:grid;place-items:center;flex:0 0 44px;height:44px;border-radius:12px;
 background:var(--lf-surface,#fff);color:var(--lf-link,#204a9b)
}
.lf-blocks-page .lf-block-copy{min-width:0;flex:1}
.lf-blocks-page .lf-block-title{display:block;font-weight:700;font-size:1.02rem;line-height:1.3}
.lf-blocks-page .lf-block-description{
 display:block;color:var(--lf-muted,#4b5870);font-size:.88rem;line-height:1.45;margin-top:5px
}
.lf-blocks-page .lf-block-preview-controls{
 display:flex;align-items:center;justify-content:flex-end;min-width:0;padding:0 12px 10px
}
.lf-blocks-page .lf-block-preview-toggle{
 display:inline-flex;align-items:center;justify-content:center;gap:7px;
 min-width:94px;min-height:42px;padding:9px 13px;border-radius:9px;
 border:1px solid var(--lf-border,#75839a);
 background:var(--lf-surface,#fff);color:var(--lf-link,#204a9b);
 font:inherit;font-size:.84rem;font-weight:700;cursor:pointer
}
.lf-blocks-page .lf-block-preview-toggle:hover{
 background:var(--lf-hover,#eaf0fa);border-color:var(--lf-link,#204a9b)
}
.lf-blocks-page .lf-block-preview-toggle .bi-chevron-down{transition:transform .24s ease}
.lf-blocks-page .lf-block-preview-toggle[aria-expanded="true"] .bi-chevron-down{transform:rotate(180deg)}
.lf-blocks-page .lf-block-preview-panel{
 display:grid;grid-template-rows:0fr;visibility:hidden;
 transition:grid-template-rows .27s ease,visibility .27s
}
.lf-blocks-page .lf-block-card.is-preview-open .lf-block-preview-panel{
 grid-template-rows:1fr;visibility:visible
}
.lf-blocks-page .lf-block-preview-content{min-width:0;min-height:0;overflow:hidden}
.lf-blocks-page .lf-block-preview-stage{
 padding:12px 14px 15px;border-top:1px solid var(--lf-border,#75839a)
}
.lf-blocks-page .lf-block-preview-caption{
 display:block;color:var(--lf-muted,#4b5870);font-size:.75rem;line-height:1.45;
 margin-bottom:8px
}
.lf-blocks-page .lf-block-preview-frame{
 display:block;width:100%;height:154px;max-width:100%;
 box-sizing:border-box;border:1px solid var(--lf-border,#75839a);
 border-radius:11px;background:var(--lf-surface,#fff);
 pointer-events:none;overflow:hidden
}
.lf-blocks-page .lf-block-preview-note{
 margin:9px 0 0;color:var(--lf-muted,#4b5870);font-size:.74rem;line-height:1.4
}
@media(max-width:575px){
 .lf-blocks-page .card-body{padding:14px!important}
 .lf-blocks-page .lf-block-choice{min-height:93px;padding:15px 15px 9px;gap:12px}
 .lf-blocks-page .lf-block-preview-controls{padding:0 12px 10px}
 .lf-blocks-page .lf-block-preview-stage{padding:11px 12px 13px}
 .lf-blocks-page .lf-block-preview-frame{height:143px}
}
@media(prefers-reduced-motion:reduce){
 .lf-blocks-page .lf-block-card,.lf-blocks-page .lf-block-preview-panel,
 .lf-blocks-page .lf-block-preview-toggle .bi-chevron-down{transition:none!important}
}
</style>
@endpush
@push('sidebar-scripts')
<script src="{{ asset('assets/js/linkfree-block-previews.js') }}?v={{ filemtime(base_path('assets/js/linkfree-block-previews.js')) }}" defer></script>
@endpush
@section('content')
@php
  // Apenas leitura do tema atual para que exemplos usem os mesmos estilos públicos.
  $previewTheme = (string) (Auth::user()->theme ?? '');
  $isTheme = $previewTheme !== 'default' && preg_match('/\A[A-Za-z0-9_-]+\z/', $previewTheme);
  $previewThemeDir = $isTheme ? base_path('themes/'.$previewTheme) : null;
  $previewConfig = $previewThemeDir && is_file($previewThemeDir.'/config.php')
    ? (include $previewThemeDir.'/config.php') : [];
  $previewDefaultBrands = is_array($previewConfig)
    && ($previewConfig['use_default_buttons'] ?? '') === 'true';
  $previewBrands = $previewThemeDir && !$previewDefaultBrands
    && is_file($previewThemeDir.'/brands.css')
    ? asset('themes/'.$previewTheme.'/brands.css')
    : asset('assets/linkstack/css/brands.css');
  $previewSkeleton = $previewThemeDir && is_file($previewThemeDir.'/skeleton-auto.css')
    ? asset('themes/'.$previewTheme.'/skeleton-auto.css')
    : asset('assets/linkstack/css/skeleton-auto.css');
@endphp
<div class="conatiner-fluid content-inner mt-n5 py-0 lf-blocks-page"
     data-preview-brand-css="{{ $previewBrands }}"
     data-preview-skeleton-css="{{ $previewSkeleton }}"
     data-preview-icon-base="{{ asset('assets/linkstack/icons') }}">
<div class="row"><div class="col-12">
<section class="card rounded" aria-label="{{ __('member_ui.manage_blocks') }}"><div class="card-body p-4">
<p class="text-muted mb-4">{{ __('messages.Select Block') }} — {{ __('messages.Click to change link blocks') }}</p>
<div class="lf-block-grid">
@foreach ($LinkTypes as $lt)
@php
  $title = !empty($lt['title']) && block_text_translation_check($lt['title'])
    ? bt($lt['title']) : __('messages.block.title.'.$lt['typename']);
  $description = !empty($lt['description'])
    ? (bt($lt['description']) ?? __('messages.block.description.'.$lt['typename']))
    : __('messages.block.description.'.$lt['typename']);
  $editorUrl = $editingLink ? route('showLink', $editingLink->id) : route('showButtons');
@endphp
<article class="lf-block-card" data-block-kind="{{ $lt['typename'] }}">
 <a class="lf-block-choice" href="{{ $editorUrl.'?block='.rawurlencode($lt['typename']) }}" aria-label="{{ __('messages.Select Block') }}: {{ $title }}">
  <span class="lf-block-icon"><i class="{{ $lt['icon'] }}" aria-hidden="true"></i></span>
  <span class="lf-block-copy"><span class="lf-block-title">{{ $title }}</span><span class="lf-block-description">{{ $description }}</span></span>
 </a>
 <div class="lf-block-preview-controls">
  <button class="lf-block-preview-toggle" type="button" aria-expanded="false"
          aria-controls="lf-block-preview-{{ $loop->index }}" aria-label="Prévia: {{ $title }}">
    <i class="bi bi-eye" aria-hidden="true"></i> Prévia
    <i class="bi bi-chevron-down" aria-hidden="true"></i>
  </button>
 </div>
 <div class="lf-block-preview-panel" id="lf-block-preview-{{ $loop->index }}" aria-hidden="true" inert>
  <div class="lf-block-preview-content">
   <div class="lf-block-preview-stage">
    <span class="lf-block-preview-caption">Visualização ilustrativa na página pública</span>
    <iframe class="lf-block-preview-frame" title="Prévia do bloco: {{ $title }}" sandbox=""
            tabindex="-1" loading="lazy" referrerpolicy="no-referrer"></iframe>
    <p class="lf-block-preview-note">O conteúdo demonstrativo não é salvo. A aparência final depende das configurações do bloco e do tema.</p>
   </div>
  </div>
 </div>
</article>
@endforeach
</div>
<div class="pt-4"><a class="btn btn-outline-secondary" href="{{ $editingLink ? route('showLink', $editingLink->id) : route('showButtons') }}">{{ __('messages.Cancel') }}</a></div>
</div></section>
</div></div>
</div>
@endsection
