@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
<style>
.lf-blocks-page .lf-block-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:14px}
.lf-blocks-page .lf-block-choice{display:flex;align-items:flex-start;gap:14px;min-height:118px;padding:20px;border-radius:14px;
 background:var(--lf-raised,#f5f7fc);border:1px solid var(--lf-border,#75839a);color:var(--lf-text,#18243b);
 text-decoration:none;overflow-wrap:anywhere;transition:background-color .15s,border-color .15s}
.lf-blocks-page .lf-block-choice:hover{background:var(--lf-hover,#eaf0fa);color:var(--lf-text,#18243b);border-color:var(--lf-link,#204a9b)}
.lf-blocks-page .lf-block-choice:focus-visible{outline:3px solid var(--lf-focus,#275fbd);outline-offset:3px}
.lf-blocks-page .lf-block-icon{display:grid;place-items:center;flex:0 0 48px;height:48px;border-radius:12px;background:var(--lf-surface,#fff);color:var(--lf-link,#204a9b)}
.lf-blocks-page .lf-block-title{display:block;font-weight:700;font-size:1.05rem;line-height:1.3}
.lf-blocks-page .lf-block-description{display:block;color:var(--lf-muted,#4b5870);font-size:.91rem;margin-top:6px}
@media(max-width:575px){.lf-blocks-page .card-body{padding:14px!important}.lf-blocks-page .lf-block-choice{padding:15px}}
</style>
@endpush
@section('content')
<div class="conatiner-fluid content-inner mt-n5 py-0 lf-blocks-page">
<div class="row"><div class="col-12">
<section class="card rounded" aria-labelledby="lf-blocks-title"><div class="card-body p-4">
<h1 class="h3 mb-2" id="lf-blocks-title"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Gerenciar blocos</h1>
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
<a class="lf-block-choice" href="{{ $editorUrl.'?block='.rawurlencode($lt['typename']) }}" aria-label="{{ __('messages.Select Block') }}: {{ $title }}">
 <span class="lf-block-icon"><i class="{{ $lt['icon'] }}" aria-hidden="true"></i></span>
 <span><span class="lf-block-title">{{ $title }}</span><span class="lf-block-description">{{ $description }}</span></span>
</a>
@endforeach
</div>
<div class="pt-4"><a class="btn btn-outline-secondary" href="{{ $editingLink ? route('showLink', $editingLink->id) : route('showButtons') }}">{{ __('messages.Cancel') }}</a></div>
</div></section>
</div></div>
</div>
@endsection
