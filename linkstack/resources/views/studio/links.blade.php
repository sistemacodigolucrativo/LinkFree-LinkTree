@extends('layouts.sidebar')

@section('content')

<?php use App\Models\Button;

// Check if the LinkCount cookie is set
if (isset($_COOKIE['LinkCount'])) {
  // Set the expiration time of the cookie to one hour in the past
  setcookie('LinkCount', '', time() - 3600);
}

?>

<style>
.sortable-handle {
    margin-right: 25px;
    width: 25px;
    height: auto;
    transform: rotate(90deg);
    cursor: grab;
    cursor: -webkit-grabbing;
    fill: currentColor;
}
</style>

@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
<style>
/* Estúdio > Meus Links: cards independentes, sem prévia embutida. */
.studio-links-page .links-workspace { width:100%; min-width:0; }
.studio-links-page .links-workspace > .card-body { padding:0; }
.studio-links-page .pre-left { width:100%; min-width:0; box-sizing:border-box; }
.studio-links-page .links-section-card { width:100%; min-width:0; overflow-wrap:anywhere; }
.studio-links-page .links-section-card .card-header { display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; justify-content:space-between; }
.studio-links-page .links-section-card .card-header > .btn { float:none!important; margin-left:auto; }
.studio-links-page .links-section-card .card-body { width:100%; min-width:0; }
.studio-links-page #links-table-body { width:100%; min-width:0; }
.studio-links-page .page-icons-card .form-group { width:100%; max-width:760px; min-width:0; }
.studio-links-page .page-icons-card .input-group { display:flex; min-width:0; flex-wrap:nowrap; }
.studio-links-page .page-icons-card .input-group input { flex:1 1 auto; min-width:0; }
.studio-links-preview-fab {
  position:fixed; right:clamp(16px,3vw,34px);
  bottom:calc(20px + env(safe-area-inset-bottom, 0px));
  z-index:1035; display:inline-flex; align-items:center; justify-content:center;
  gap:.5rem; min-width:56px; min-height:56px; padding:12px 19px;
  border:2px solid var(--lf-border); border-radius:100px; background:var(--lf-raised)!important;
  color:var(--lf-text)!important; font-size:1rem; font-weight:700; line-height:1.2;
  box-shadow:0 8px 28px var(--lf-shadow-floating); cursor:pointer;
}
.studio-links-preview-fab:hover, .studio-links-preview-fab:active { background:var(--lf-hover)!important; color:var(--lf-text)!important; }
.studio-links-preview-fab:focus-visible { outline:3px solid var(--lf-focus); outline-offset:3px; }
.studio-links-preview-modal .modal-content { position:relative; height:100vh; height:100dvh; display:flex; flex-direction:column; background:var(--lf-surface); border:0; }
.studio-links-preview-modal .studio-links-preview-close {
 position:absolute; z-index:10; inset-inline-start:calc(12px + env(safe-area-inset-left,0px));
 top:calc(10px + env(safe-area-inset-top,0px)); width:46px; height:46px;
 border:1px solid var(--lf-border); border-radius:50%; background:var(--lf-surface); color:var(--lf-text);
 display:grid; place-items:center; padding:0; font:700 30px/1 system-ui,sans-serif;
 box-shadow:0 2px 8px var(--lf-shadow-close); cursor:pointer;
}
.studio-links-preview-modal .studio-links-preview-close:hover {background:var(--lf-hover)}
.studio-links-preview-modal .studio-links-preview-close:focus-visible {outline:3px solid var(--lf-focus); outline-offset:3px}
.studio-links-preview-modal .modal-body { flex:1 1 auto; min-height:0; overflow:hidden; }
.studio-links-preview-modal iframe { display:block; width:100%; height:100%; border:0; background:#fff; }
@media(max-width:767px) {
  .studio-links-page .links-section-card .card-body { padding:14px!important; }
  .studio-links-page .links-section-card .card-header > .btn { width:100%; margin-left:0; }
  .studio-links-preview-fab { right:16px; bottom:calc(16px + env(safe-area-inset-bottom, 0px)); }
}
@media(max-width:359px) { .studio-links-preview-fab { padding:12px 15px; } }
</style>
<style>.delete{position:relative; color:transparent; background-color:var(--lf-danger-action); border-radius:5px; left:5px; padding:5px 12px; cursor: pointer;}.delete:hover{color:transparent;background-color:var(--lf-danger-action-hover);}html,body{max-width:100%;overflow-x:hidden;}</style>
<style>
@media(max-width:767px){
 .pre-left{min-width:0}
 .pre-left>.card-header{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center}
 .pre-left .col.ms-3{margin-left:0!important;width:100%;min-width:0}
 #links-table-body .row{margin-inline:0}
 #links-table-body .col{min-width:0;overflow-wrap:anywhere}
 #links-table-body [data-id]>.d-flex{padding-inline:0}
 #links-table-body .col-12.p-2{max-width:100%!important;white-space:normal!important;overflow-wrap:anywhere}
 #links-table-body .col-12.py-1{display:flex;flex-wrap:wrap;gap:.35rem;padding-inline:.25rem!important}
 #links-table-body .col-12.py-1>a,#links-table-body .col-12.py-1>button{float:none!important;min-width:44px;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
 #links-table-body img{max-width:100%}
}

/* Mesmo alinhamento e mesma largura externa para os dois cards. */
.studio-links-page .studio-links-sections{display:flex;flex-direction:column;gap:16px;min-width:0;width:100%}
.studio-links-page .studio-links-sections>.links-section-card{
 display:block;flex:0 0 auto;width:100%!important;max-width:none!important;
 margin:0!important;min-width:0;box-sizing:border-box}
.studio-links-page .studio-links-sections .links-section-card>.card-body{
 width:100%;max-width:100%;min-width:0;box-sizing:border-box}
.studio-links-page .page-icons-card .form-group{max-width:100%;width:100%}
/* Card slim do link: título, endereço e controles sem grandes espaços vazios. */
.studio-links-page .studio-link-slim{min-height:56px;margin-inline:0!important;
 padding:5px 8px!important;align-items:center;background:var(--bs-card-bg,transparent)}
.studio-links-page .studio-link-slim>.d-flex{width:100%;min-width:0;align-items:center;gap:6px}
.studio-links-page .studio-link-slim .h-100{height:auto!important}
.studio-links-page .studio-link-slim>.d-flex>.col-auto{flex:0 0 30px;padding:4px!important;margin:0!important}
.studio-links-page .studio-link-slim .sortable-handle{width:23px;margin-right:0}
.studio-links-page .studio-link-slim>.d-flex>.col{min-width:0;flex:1}
.studio-links-page .studio-link-slim>.d-flex>.col>.row{
 margin:0;display:flex;align-items:center;flex-wrap:wrap;column-gap:10px;row-gap:4px}
.studio-links-page .studio-link-slim .col-12.p-2{
 flex:1 1 220px;width:auto;min-width:0;max-width:none!important;padding:3px 0!important;margin:0}
.studio-links-page .studio-link-slim .col-12.p-2 .h6{display:inline-block;max-width:100%;line-height:1.35;margin:0}
.studio-links-page .studio-link-slim .col.text-end{flex:0 1 auto;min-width:0;max-width:100%;font-size:.8rem}
.studio-links-page .studio-link-slim .col.text-end img{max-height:45px!important;width:auto}
.studio-links-page .studio-link-slim .col-12.py-1{
 flex:1 1 auto;width:auto;max-width:100%;min-width:0;padding:3px 0!important;
 margin:0!important;display:flex;flex-wrap:wrap;gap:5px;align-items:center;justify-content:flex-end}
.studio-links-page .studio-link-slim .col-12.py-1 :is(a,button){
 float:none!important;margin:0!important;flex:0 0 auto;min-width:40px;min-height:40px;
 display:inline-flex;align-items:center;justify-content:center}
.studio-links-page .studio-link-slim .col-12.py-1>span{
 margin-right:auto;font-size:.8rem;white-space:nowrap}
@media(max-width:575px){
 .studio-links-page .studio-link-slim{padding:4px 6px!important}
 .studio-links-page .studio-link-slim .col-12.py-1{justify-content:flex-start}
 /* A área clicável permanece acessível, mas o ÍCONE visual é pequeno. */
 .studio-links-page .studio-link-slim .col-12.py-1 :is(a,button){min-width:40px;min-height:40px}
}
/* Controles slim de edição/exclusão/arraste (anotação 03). */
.studio-links-page .studio-link-slim{
 min-height:48px!important;padding:4px 8px!important;margin-bottom:8px!important;
}
.studio-links-page .studio-link-slim>.d-flex{gap:4px;align-items:center}
.studio-links-page .studio-link-slim>.d-flex>.col-auto{
 flex:0 0 38px!important;max-width:38px!important;width:38px!important;
 min-width:38px!important;min-height:42px;
 margin:0!important;padding:0!important;display:flex;align-items:center;justify-content:center;
}
.studio-links-page .studio-link-slim .sortable-handle{
 width:16px!important;height:16px!important;max-width:16px;
 min-width:16px;margin:0!important;touch-action:pan-y;
 cursor:grab;
}
.studio-links-page .studio-link-slim .col-12.p-2{padding:2px 0!important}
.studio-links-page .studio-link-slim .col-12.p-2 .h6{font-size:.87rem;line-height:1.25}
.studio-links-page .studio-link-slim .col-12.p-2 a{font-size:.78rem;line-height:1.25}
.studio-links-page .studio-link-slim .col-12.py-1{
 padding:0!important;margin:2px 0 0!important;gap:4px!important;
 align-items:center;justify-content:flex-end;
}
.studio-links-page .studio-link-slim .col-12.py-1 a.btn-icon,
.studio-links-page .studio-link-slim .col-12.py-1 button.btn-icon{
 display:inline-flex!important;align-items:center!important;justify-content:center!important;
 width:38px!important;height:38px!important;min-width:38px!important;
 min-height:38px!important;max-width:38px!important;max-height:38px!important;
 padding:7px!important;margin:0!important;border-radius:9px;
}
.studio-links-page .studio-link-slim .col-12.py-1 a.btn-icon :is(svg,.icon,.icon-20),
.studio-links-page .studio-link-slim .col-12.py-1 button.btn-icon :is(svg,.icon,.icon-20){
 width:15px!important;height:15px!important;max-width:15px!important;max-height:15px!important;
}
.studio-links-page .studio-link-slim .col-12.py-1 .btn-inner{
 display:inline-flex;align-items:center;justify-content:center;
 width:16px!important;height:16px!important;padding:0!important;margin:0!important;
}
.studio-links-page .studio-link-slim .col-12.py-1>span{
 font-size:.76rem;line-height:1.2;margin-right:auto;
}
.studio-links-page .studio-link-slim > .d-flex > .col > .row{row-gap:2px}
.studio-links-page .studio-link-slim .d-flex.gap-1{
 padding:0 0 0 38px!important;gap:4px!important;margin-top:0!important;
}
.studio-links-page .studio-link-slim .d-flex.gap-1 .btn{
 width:36px;min-width:36px;min-height:36px;height:36px;padding:4px!important;
 font-size:.9rem;line-height:1;
}
@media(max-width:575px){
 .studio-links-page .studio-link-slim .col-12.py-1 a.btn-icon,
 .studio-links-page .studio-link-slim .col-12.py-1 button.btn-icon{
   width:40px!important;height:40px!important;min-width:40px!important;min-height:40px!important;
   max-width:40px!important;max-height:40px!important;
 }
 .studio-links-page .studio-link-slim .d-flex.gap-1 .btn{min-height:40px;height:40px;min-width:40px;width:40px}
 .studio-links-page .studio-link-slim>.d-flex>.col-auto{min-height:44px}
}

/* Meus Links — ações inline, controles visuais menores e cliques no topo. */
.studio-links-page #links-table-body .studio-link-slim{
  position:relative!important;display:block;min-width:0;
  padding:5px 7px!important;min-height:0!important;box-sizing:border-box;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-clicks{
  position:absolute;top:5px;right:7px;z-index:1;width:max-content!important;max-width:105px;
  padding:3px 6px;font-size:10px;line-height:1.2;font-weight:700;
  display:inline-flex;align-items:center;gap:3px;white-space:nowrap;
  color:var(--lf-text,#18243b);background:var(--lf-surface,#fff);
  border:1px solid var(--lf-border,#75839a);border-radius:7px;
}
.studio-links-page #links-table-body .studio-link-slim.has-clicks .col-12.p-2:first-child{
  padding-right:110px!important;
}
.studio-links-page #links-table-body .studio-link-slim>.d-flex>.col-auto{
  flex:0 0 30px!important;width:30px!important;min-width:30px!important;
  max-width:30px!important;min-height:36px!important;
  margin:0!important;padding:0!important;
}
.studio-links-page #links-table-body .studio-link-slim .sortable-handle{
  width:14px!important;height:14px!important;min-width:14px!important;
  max-width:14px!important;transform:rotate(90deg);margin:0!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions{
  width:100%!important;max-width:100%!important;min-width:0!important;
  display:flex!important;flex-direction:row!important;align-items:center!important;
  justify-content:flex-start!important;flex-wrap:nowrap!important;gap:5px!important;
  overflow-x:auto;overflow-y:hidden;scrollbar-width:thin;
  white-space:nowrap;margin:3px 0 0!important;padding:1px 0 2px!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions :is(a.btn-icon,button.btn-icon,button.studio-link-move){
  position:static!important;float:none!important;display:inline-flex!important;
  justify-content:center!important;align-items:center!important;
  flex:0 0 34px!important;box-sizing:border-box!important;
  width:34px!important;height:34px!important;min-width:34px!important;
  min-height:34px!important;max-width:34px!important;max-height:34px!important;
  margin:0!important;padding:5px!important;border-radius:8px!important;
  font-size:14px!important;line-height:1!important;box-shadow:none!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions :is(a.btn-icon,button.btn-icon) .btn-inner{
  width:15px!important;height:15px!important;padding:0!important;
  display:inline-flex!important;align-items:center!important;justify-content:center!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions :is(svg,.icon-20){
  display:block;width:14px!important;height:14px!important;
  min-width:14px!important;min-height:14px!important;
  max-width:14px!important;max-height:14px!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions .studio-link-move i{
  font-size:14px!important;line-height:1!important;
}
.studio-links-page #links-table-body .studio-link-slim .studio-link-actions>a:not(.btn-icon){
  float:none!important;white-space:nowrap;flex:0 0 auto;margin:0!important;
}
@media(max-width:575px){
 .studio-links-page #links-table-body .studio-link-slim .studio-link-actions :is(a.btn-icon,button.btn-icon,button.studio-link-move){
  width:36px!important;height:36px!important;min-width:36px!important;
  min-height:36px!important;max-width:36px!important;max-height:36px!important;
  flex-basis:36px!important;
 }
 .studio-links-page #links-table-body .studio-link-slim.has-clicks .col-12.p-2:first-child{padding-right:100px!important}
}
</style>
@endpush

@include('components.favicon')
@include('components.favicon-extension')

<?php if(!function_exists('strp')){function strp($urlStrp){return str_replace(array('http://', 'https://'), '', $urlStrp);}} ?>

<div class="conatiner-fluid content-inner mt-n5 py-0 studio-links-page">
    <div class="row">

        <div class="col-lg-12">
            <div class="links-workspace">
               <div class="card-body">
                  <div class="row">
                      <div class="col-sm-12">

                        <div class="studio-links-sections">
                            <section class='pre-left links-section-card card rounded mb-3 text-gray-400' aria-labelledby='studio-my-links-title'>
                                <h3 id="studio-my-links-title" class="card-header mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <span><i class="bi bi-link-45deg" aria-hidden="true"></i> {{__('messages.My Links')}}</span>
                                    <a class="btn btn-primary" href="{{ url('/studio/add-link') }}">{{__('messages.Add new Link')}}</a>
                                </h3>

                                <div class="card-body px-3 pb-3">

                                    {{-- <div style="text-align: right;"><a href="{{ url('/studio/links') }}/10">10</a> | <a href="{{ url('/studio/links') }}/20">20</a> | <a href="{{ url('/studio/links') }}/30">30</a> | <a href="{{ url('/studio/links') }}/all">all</a></div> --}}

                                <div class="col-12 px-1">

                                    <div id="links-table-body" data-page="{{request('page', 1)}}" data-per-page="{{$pagePage ? $pagePage : 0}}">
                                        @if($links->total() == 0)
                                              <div class="col-12 text-center">
                                                <p class="my-4">{{__('messages.No Link Added')}}</p>
                                              </div>
                                        @else
                                        @foreach($links as $link)
                                        @php $button = Button::find($link->button_id); if(isset($button->name)){$buttonName = $button->name;}else{$buttonName = 0;} @endphp
                                        @php if($buttonName == "default email"){$buttonName = "email";} if($buttonName == "default email_alt"){$buttonName = "email_alt";} @endphp
                                        @if($button && $button->name !== 'icon')
                                        <div class='row studio-link-slim pb-0 mb-2 border rounded w-100 {{ !empty($link->link) ? 'has-clicks' : '' }}' data-id="{{$link->id}}">
                                            @if(!empty($link->link))
                                              <span class="studio-link-clicks" aria-label="{{ $link->click_number }} {{__('messages.Clicks')}}">
                                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i> {{ $link->click_number }} {{__('messages.Clicks')}}
                                              </span>
                                            @endif
                                            <div class="d-flex ">


                                                <div class='col-auto p-2 my-auto mr-2' title="{{ $link->link }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="sortable-handle" viewBox="0 0 16 16">
                                                        <path d="M1 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V4zM1 9a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V9zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V9z"/>
                                                      </svg>
                                                </div>

                                            <div class='col h-100'>

                                                <div class='row h-100'>
                                                    <div class='col-12 p-2' style="max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="{{ $link->title }}">
                                                        <span class='h6'>
                                                            @if($button->name == "custom_website")
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><img style="margin-bottom:3px;margin-left:4px;margin-right:4px;max-width:15px;max-height:15px;" alt="button-icon" class="icon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($link->id))){{url('assets/favicon/icons/'.localIcon($link->id))}}@else{{getFavIcon($link->id)}}@endif" onerror="this.onerror=null; this.src='{{asset('assets/linkstack/icons/website.svg')}}';"></span>
                                                            @elseif($button->name == "space")
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><i style="margin-left:2.83px;margin-right:-1px;color:var(--lf-text);" class='bi bi-distribute-vertical'>&nbsp;</i></span>
                                                            @elseif($button->name == "heading")
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><i style="margin-left:2.83px;margin-right:-1px;color:var(--lf-text);" class='bi bi-card-heading'>&nbsp;</i></span>
                                                            @elseif($button->name == "text")
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><i style="margin-left:2.83px;margin-right:-1px;color:var(--lf-text);" class='bi bi-fonts'>&nbsp;</i></span>
                                                            @elseif($link->custom_icon && $link->type && $link->type !== 'predefined')
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><i style="width:20px;margin:1px;color:var(--lf-text);" class='fa {{$link->custom_icon}}'>&nbsp;</i></span>
                                                            @else
                                                            <span class="bg-soft-secondary" style="border: 1px solid var(--lf-border) !important;border-radius:5px;width:25px!important;height:25px!important;"><img style="max-width:15px !important;" alt="button-icon" height="15" class="m-1 " src="{{ asset('\/assets/linkstack/icons\/') . $buttonName }}.svg "></span>
                                                            @endif

                                                            {{strip_tags($link->title,'')}}</span>

                                                        @if(!empty($link->link) and $button->name != "vcard")
                                                        <br>
                                                        <a title='{{$link->link}}' href="{{ $link->link}}" target="_blank" class="d-none d-md-block ml-4 text-muted small">{{Str::limit($link->link, 75 )}}</a>
                                                        <a title='{{$link->link}}' href="{{ $link->link}}" target="_blank" class="d-md-none ml-4 text-muted small">{{Str::limit($link->link, 25 )}}</a>
                                                        @elseif(!empty($link->link) and $button->name == "vcard")
                                                        <br><a href="{{ url('vcard/'.$link->id) }}" target="_blank" class="ml-4 small">{{__('messages.Download')}}</a>

                                                        @endif

                                                    </div>

                                                    <div class="col text-end">
                                                        {{Str::limit($link->params['text'] ?? null, 150)  }}

                                                        @if($link->typename == 'video')
                                                            @php
                                                                $embed = OEmbed::get($link->link);
                                                                if ($embed && $embed->hasThumbnail()) {
                                                                    echo "<img style='max-height: 150px;' src='".$embed->thumbnailUrl()."' />";

                                                                }
                                                            @endphp

                                                        @endif
                                                    </div>


                                                    <div class='col-12 py-1 px-3 m-0 mt-2 studio-link-actions' role="group" aria-label="Ações do link">

                                                        <a style="float: right;" href="{{ route('deleteLink', $link->id ) }}" data-delete-link data-confirm="{{ __('messages.confirm_delete', ['title' => $link->title]) }}" class="btn btn-sm me-1 btn-icon btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Delete" data-original-title="{{__('messages.Delete')}}">
                                                            <span class="btn-inner">
                                                               <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor">
                                                                  <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                  <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                  <path d="M17.4406 6.23973C16.6556 6.23973 15.9796 5.68473 15.8256 4.91573L15.5826 3.69973C15.4326 3.13873 14.9246 2.75073 14.3456 2.75073H10.1126C9.53358 2.75073 9.02558 3.13873 8.87558 3.69973L8.63258 4.91573C8.47858 5.68473 7.80258 6.23973 7.01758 6.23973" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                               </svg>
                                                            </span>
                                                         </a>

                                                            <a style="float: right;" href="{{ route('editLink', $link->id ) }}" class="btn btn-sm me-1 btn-icon btn-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-original-title="{{__('messages.Edit')}}" aria-label="Edit" data-bs-original-title="{{__('messages.Edit')}}">
                                                               <span class="btn-inner">
                                                                  <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                     <path d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                     <path fill-rule="evenodd" clip-rule="evenodd" d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                     <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                  </svg>
                                                               </span>
                                                            </a>

                                                            <button type="button" class="btn btn-sm btn-outline-secondary studio-link-move" data-direction="-1" aria-label="Mover link para cima" title="Mover para cima">
                                                               <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                                             </button>
                                                             <button type="button" class="btn btn-sm btn-outline-secondary studio-link-move" data-direction="1" aria-label="Mover link para baixo" title="Mover para baixo">
                                                               <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                                             </button>
                                                             @if(env('ENABLE_BUTTON_EDITOR') === true)
                                                            @if(($link->button_id == '1' || $link->button_id == '2') && $link->type == 'link')
                                                                <a style="float: right;" href="{{ route('editCSS', $link->id ) }}" class="btn btn-sm me-1 btn-icon btn-success" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Editar opções do link" data-bs-placement="top" data-original-title="{{__('messages.Customize')}}">
                                                                    <span class="btn-inner">
                                                                        <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor">
                                                                          <path fill-rule="evenodd" clip-rule="evenodd" d="M20.8064 7.62361L20.184 6.54352C19.6574 5.6296 18.4905 5.31432 17.5753 5.83872V5.83872C17.1397 6.09534 16.6198 6.16815 16.1305 6.04109C15.6411 5.91402 15.2224 5.59752 14.9666 5.16137C14.8021 4.88415 14.7137 4.56839 14.7103 4.24604V4.24604C14.7251 3.72922 14.5302 3.2284 14.1698 2.85767C13.8094 2.48694 13.3143 2.27786 12.7973 2.27808H11.5433C11.0367 2.27807 10.5511 2.47991 10.1938 2.83895C9.83644 3.19798 9.63693 3.68459 9.63937 4.19112V4.19112C9.62435 5.23693 8.77224 6.07681 7.72632 6.0767C7.40397 6.07336 7.08821 5.98494 6.81099 5.82041V5.82041C5.89582 5.29601 4.72887 5.61129 4.20229 6.52522L3.5341 7.62361C3.00817 8.53639 3.31916 9.70261 4.22975 10.2323V10.2323C4.82166 10.574 5.18629 11.2056 5.18629 11.8891C5.18629 12.5725 4.82166 13.2041 4.22975 13.5458V13.5458C3.32031 14.0719 3.00898 15.2353 3.5341 16.1454V16.1454L4.16568 17.2346C4.4124 17.6798 4.82636 18.0083 5.31595 18.1474C5.80554 18.2866 6.3304 18.2249 6.77438 17.976V17.976C7.21084 17.7213 7.73094 17.6516 8.2191 17.7822C8.70725 17.9128 9.12299 18.233 9.37392 18.6717C9.53845 18.9489 9.62686 19.2646 9.63021 19.587V19.587C9.63021 20.6435 10.4867 21.5 11.5433 21.5H12.7973C13.8502 21.5001 14.7053 20.6491 14.7103 19.5962V19.5962C14.7079 19.088 14.9086 18.6 15.2679 18.2407C15.6272 17.8814 16.1152 17.6807 16.6233 17.6831C16.9449 17.6917 17.2594 17.7798 17.5387 17.9394V17.9394C18.4515 18.4653 19.6177 18.1544 20.1474 17.2438V17.2438L20.8064 16.1454C21.0615 15.7075 21.1315 15.186 21.001 14.6964C20.8704 14.2067 20.55 13.7894 20.1108 13.5367V13.5367C19.6715 13.284 19.3511 12.8666 19.2206 12.3769C19.09 11.8873 19.16 11.3658 19.4151 10.928C19.581 10.6383 19.8211 10.3982 20.1108 10.2323V10.2323C21.0159 9.70289 21.3262 8.54349 20.8064 7.63277V7.63277V7.62361Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                          <circle cx="12.1747" cy="11.8891" r="2.63616" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle>
                                                                      </svg>
                                                                     </span>
                                                                </a>
                                                            @endif
                                                            @endif

                                                        @if(file_exists(base_path("assets/favicon/icons/").localIcon($link->id)))<a style="float: right;" href="{{ route('clearIcon', $link->id ) }}" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Limpar cache do ícone de {{ $link->title }}" data-original-title="Clear icon cache" class="float-right hvr-grow p-1 text-primary"><i style="-webkit-text-stroke:1px;padding-right:5px;" class="bi bi-arrow-repeat"></i></a>@endif

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                        @endif
                                        @endforeach
                                        @endif
                                    </div>


                                    <script type="text/javascript">
                                        const linksTableOrders = "{{ implode(' | ', $links->pluck('id')->toArray()) }}"
                                    </script>
                                </div>

                                <div class="d-flex justify-content-center">
                                    {!! $links ?? ''->links() !!}
                                </div>

                                @if(count($links) > 3)<a class="btn btn-primary" href="{{ url('/studio/add-link') }}">{{__('messages.Add new Link')}}</a>@endif
                                </div>
                            </section>

                            {{-- A prévia foi movida para um modal fullscreen acionado pelo botão flutuante. --}}

                            <section class='links-section-card page-icons-card card rounded mt-3 mb-3 text-gray-400' aria-labelledby='studio-page-icons-title'>
                            <a name="icons"></a>
                            <h3 id="studio-page-icons-title" class="mb-3 card-header"><i class="fa-solid fa-icons"></i> {{__('messages.Page Icons')}}</h3>
                            <div class="card-body p-3">

                            <form action="{{ route('editIcons') }}" enctype="multipart/form-data" method="post">
                                @csrf
                                <div class="form-group col-lg-8">

                                    @php
                                    if (!function_exists('iconLink')) {
                                        function iconLink($icon) {
                                            $iconLink = DB::table('links')
                                                ->where('user_id', Auth::id())
                                                ->where('title', $icon)
                                                ->where('button_id', 94)
                                                ->value('link');
                                            if (is_null($iconLink)){
                                                return false;
                                            } else {
                                                return $iconLink;
                                            }
                                        }
                                    }

                                    if (!function_exists('searchIcon')) {
                                        function searchIcon($icon) {
                                            $iconId = DB::table('links')
                                                ->where('user_id', Auth::id())
                                                ->where('title', $icon)
                                                ->where('button_id', 94)
                                                ->value('id');
                                            if (is_null($iconId)){
                                                return false;
                                            } else {
                                                return $iconId;
                                            }
                                        }
                                    }

                                    if (!function_exists('iconclicks')) {
                                        function iconclicks($icon) {
                                            $iconClicks = searchIcon($icon);
                                            $iconClicks = DB::table('links')->where('id', $iconClicks)->value('click_number');
                                            if (is_null($iconClicks)){
                                                return 0;
                                            } else {
                                                return $iconClicks;
                                            }
                                        }
                                    }

                                    if (!function_exists('icon')) {
                                        function icon($name, $label) {
                                            echo '<div class="mb-3">
                                                    <label class="form-label">'.$label.'</label>
                                                    <span class="form-text" style="font-size: 90%; font-style: italic;">'.__('messages.Clicks').': '.iconclicks($name).'</span>
                                                    <div class="input-group">
                                                      <span class="input-group-text"><i class="fab fa-'.$name.'"></i></span>
                                                      <input type="url" class="form-control" name="'.$name.'" value="'.iconLink($name).'" />
                                                      '.(searchIcon($name) != NULL ? '<a href="'.route("deleteLink", searchIcon($name)).'" aria-label="Excluir ícone de '.e($label).'" class="btn btn-danger"><i class="bi bi-trash-fill"></i></a>' : '').'
                                                    </div>
                                                  </div>';
                                        }
                                    }
                                    @endphp
                                    <style>input{border-top-right-radius: 0.25rem!important; border-bottom-right-radius: 0.25rem!important;}</style>


                                {!!icon('mastodon', 'Mastodon')!!}

                                {!!icon('instagram', 'Instagram')!!}

                                {!!icon('x-twitter', 'X')!!}

                                {!!icon('facebook', 'Facebook')!!}

                                {!!icon('github', 'GitHub')!!}

                                {!!icon('twitch', 'Twitch')!!}

                                {!!icon('linkedin', 'LinkedIn')!!}

                                {!!icon('tiktok', 'TikTok')!!}

                                {!!icon('discord', 'Discord')!!}

                                {!!icon('youtube', 'YouTube')!!}

                                {!!icon('snapchat', 'Snapchat')!!}

                                {!!icon('reddit', 'Reddit')!!}

                                {!!icon('pinterest', 'Pinterest')!!}

                                {!!icon('telegram', 'Telegram')!!}

                                {!!icon('whatsapp', 'WhatsApp')!!}

                                {!! icon('behance', 'Behance') !!}

                                {!! icon('dribbble', 'Dribble') !!}

                                {!! icon('bluesky', 'Bluesky') !!}

                                {!! icon('threads', 'Threads') !!}



                                <button type="submit" class="mt-3 ml-3 btn btn-primary">{{__('messages.Save links')}}</button>
                            </form>


                            </div>
                            </section>
                        </div>

                      </div>
                  </div>
               </div>
            </div>
         </div>


      </div>
    </div>

{{-- Botão flutuante independente dos cards, disponível durante a edição. --}}
<button id="studio-links-preview-trigger" class="studio-links-preview-fab" type="button"
        data-bs-toggle="modal" data-bs-target="#studioLinksPreviewModal"
        aria-label="Abrir pré-visualização da minha página em tela inteira"
        title="Pré-visualizar minha página">
  <i class="bi bi-eye" aria-hidden="true"></i><span>Prévia</span>
</button>

{{-- Modal do Bootstrap: ao fechar, mantém o scroll e o estado dos formulários da página. --}}
<div class="modal fade studio-links-preview-modal" id="studioLinksPreviewModal" tabindex="-1"
     aria-label="Pré-visualização da página pública" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <button type="button" class="studio-links-preview-close" data-bs-dismiss="modal"
              aria-label="Fechar pré-visualização e voltar aos meus links" title="Fechar prévia">
        <span aria-hidden="true">&times;</span>
      </button>
      <div class="modal-body p-0">
        <iframe id="studio-links-preview-frame" title="Página pública de links do usuário"
                data-preview-url="{{ url('/@'.Auth::user()->littlelink_name) }}"
                loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('studioLinksPreviewModal');
  const frame = document.getElementById('studio-links-preview-frame');
  if (!modal || !frame) return;
  modal.addEventListener('show.bs.modal', function () {
    // Recarrega apenas a prévia, nunca a página do editor.
    frame.src = frame.dataset.previewUrl;
  });
  modal.addEventListener('hidden.bs.modal', function () {
    // Para eventuais vídeos e áudio quando a prévia é fechada.
    frame.removeAttribute('src');
  });
});
</script>

@endsection
