<?php use App\Models\UserData; ?>
@extends('layouts.sidebar')

@section('content')

<div class="conatiner-fluid content-inner mt-n5 py-0 studio-profile-editor-page">
  <div class="row">

   <div class="col-lg-12">
      <div class="card   rounded">
          <div class="card-body">
             <div class="row">
                 <div class="col-sm-12">

                  <style>
                    .ck-editor__editable[role="textbox"] {
                        /* editing area */
                        min-height: 200px;
                    }

                    .ck-content .image {
                        /* block images */
                        max-width: 80%;
                        margin: 20px auto;
                    }

                    /* The vendor profile selector has margin-top:-70px; with the
                       slim welcome header it pushes the avatar over the header.
                       Restrict this correction to the profile/page editor. */
                    .studio-profile-editor-page .profile-img.profile-logo {
                        margin-top:0!important;
                        margin-bottom:16px!important;
                        width:max-content;
                        max-width:100%;
                        display:block;
                        position:relative;
                        z-index:0;
                    }
                    .studio-profile-editor-page .profile-img.profile-logo > img {
                        display:block;
                        width:100px;
                        height:100px;
                        max-width:100%;
                        border-radius:50%;
                        object-fit:cover;
                    }
                    .studio-profile-editor-page .profile-img.profile-logo .upload-icone {
                        z-index:1;
                    }

                    /* CKEditor has a separate editing canvas. In dark mode the
                       previous global white foreground sat on a white canvas.
                       Keep editor, toolbar, popovers and placeholder in sync. */
                    .studio-profile-editor-page .ck.ck-editor__main > .ck-editor__editable,
                    .studio-profile-editor-page .ck.ck-editor__editable.ck-content {
                        background:var(--lf-editor-bg)!important;
                        color:var(--lf-editor-fg)!important;
                        border-color:var(--lf-editor-edge)!important;
                        color-scheme:normal;
                        min-height:200px;
                    }
                    .studio-profile-editor-page .ck.ck-editor__editable.ck-content :is(p,h1,h2,h3,h4,h5,h6,li,blockquote):not([style*="color"]) {
                        color:inherit!important;
                    }
                    .studio-profile-editor-page .ck.ck-editor__editable.ck-content .ck-placeholder::before {
                        color:var(--lf-editor-hint)!important;
                    }
                    .studio-profile-editor-page .ck.ck-toolbar,
                    .studio-profile-editor-page .ck.ck-dropdown__panel,
                    .studio-profile-editor-page .ck.ck-balloon-panel,
                    .studio-profile-editor-page .ck.ck-list,
                    .studio-profile-editor-page .ck.ck-input,
                    .studio-profile-editor-page .ck.ck-reset_all {
                        --ck-color-base-background:var(--lf-editor-ui);
                        --ck-color-base-foreground:var(--lf-editor-fg);
                        --ck-color-text:var(--lf-editor-fg);
                        --ck-color-toolbar-background:var(--lf-editor-ui);
                        --ck-color-dropdown-panel-background:var(--lf-editor-ui);
                        --ck-color-list-background:var(--lf-editor-ui);
                        --ck-color-input-background:var(--lf-editor-bg);
                        --ck-color-input-text:var(--lf-editor-fg);
                        --ck-color-base-border:var(--lf-editor-edge);
                    }
                    .studio-profile-editor-page .ck.ck-toolbar,
                    .studio-profile-editor-page .ck.ck-dropdown__panel,
                    .studio-profile-editor-page .ck.ck-balloon-panel {
                        background:var(--lf-editor-ui)!important;
                        color:var(--lf-editor-fg)!important;
                        border-color:var(--lf-editor-edge)!important;
                    }
                    .studio-profile-editor-page .ck.ck-toolbar :is(.ck-button,.ck-label,.ck-icon),
                    .studio-profile-editor-page .ck.ck-dropdown__panel :is(.ck-button,.ck-label) {
                        color:var(--lf-editor-fg)!important;
                    }
                    .studio-profile-editor-page textarea#field-pageDescription:not(.ckeditor) {
                        background:var(--lf-editor-bg)!important;
                        color:var(--lf-editor-fg)!important;
                        border-color:var(--lf-editor-edge)!important;
                    }
                </style>
                <style>
                    @supports (-webkit-appearance: none) or (-moz-appearance: none) {
                      input[type=checkbox],
                      input[type=radio] {
                      --active: var(--bs-primary);
                      --active-inner: var(--lf-on-primary);
                      --focus: 2px var(--lf-focus);
                      --border: var(--lf-border);
                      --border-hover: var(--lf-link);
                      --background: var(--lf-input);
                      --disabled: var(--lf-disabled-bg);
                      --disabled-inner: var(--lf-disabled-bg);
                      -webkit-appearance: none;
                      -moz-appearance: none;
                      height: 21px;
                      outline: none;
                      display: inline-block;
                      vertical-align: top;
                      position: relative;
                      margin: 0;
                      cursor: pointer;
                      border: 1px solid var(--bc, var(--border));
                      background: var(--b, var(--background));
                      transition: background 0.3s, border-color 0.3s, box-shadow 0.2s;
                      }
                    input[type=checkbox]:after,
                    input[type=radio]:after {
                        content: "";
                        display: block;
                        left: 0;
                        top: 0;
                        position: absolute;
                        transition: transform var(--d-t, 0.3s) var(--d-t-e, ease), opacity var(--d-o, 0.2s);
                      }
                      input[type=checkbox]:checked,
                    input[type=radio]:checked {
                        --b: var(--active);
                        --bc: var(--active);
                        --d-o: .3s;
                        --d-t: .6s;
                        --d-t-e: cubic-bezier(.2, .85, .32, 1.2);
                      }
                      input[type=checkbox]:disabled,
                    input[type=radio]:disabled {
                        --b: var(--disabled);
                        cursor: not-allowed;
                        opacity: 0.9;
                      }
                      input[type=checkbox]:disabled:checked,
                    input[type=radio]:disabled:checked {
                        --b: var(--disabled-inner);
                        --bc: var(--border);
                      }
                      input[type=checkbox]:disabled + label,
                    input[type=radio]:disabled + label {
                        cursor: not-allowed;
                      }
                      input[type=checkbox]:hover:not(:checked):not(:disabled),
                    input[type=radio]:hover:not(:checked):not(:disabled) {
                        --bc: var(--border-hover);
                      }
                      input[type=checkbox]:focus,
                    input[type=radio]:focus {
                        box-shadow: 0 0 0 var(--focus);
                      }
                      input[type=checkbox]:not(.switch),
                    input[type=radio]:not(.switch) {
                        width: 21px;
                      }
                      input[type=checkbox]:not(.switch):after,
                    input[type=radio]:not(.switch):after {
                        opacity: var(--o, 0);
                      }
                      input[type=checkbox]:not(.switch):checked,
                    input[type=radio]:not(.switch):checked {
                        --o: 1;
                      }
                      input[type=checkbox] + label,
                    input[type=radio] + label {
                        font-size: 14px;
                        line-height: 21px;
                        display: inline-block;
                        vertical-align: top;
                        cursor: pointer;
                        margin-left: 4px;
                      }

                      input[type=checkbox]:not(.switch) {
                        border-radius: 7px;
                      }
                      input[type=checkbox]:not(.switch):after {
                        width: 5px;
                        height: 9px;
                        border: 2px solid var(--active-inner);
                        border-top: 0;
                        border-left: 0;
                        left: 7px;
                        top: 4px;
                        transform: rotate(var(--r, 20deg));
                      }
                      input[type=checkbox]:not(.switch):checked {
                        --r: 43deg;
                      }
                      input[type=checkbox].switch {
                        width: 38px;
                        border-radius: 11px;
                      }
                      input[type=checkbox].switch:after {
                        left: 2px;
                        top: 2px;
                        border-radius: 50%;
                        width: 15px;
                        height: 15px;
                        background: var(--ab, var(--border));
                        transform: translateX(var(--x, 0));
                      }
                      input[type=checkbox].switch:checked {
                        --ab: var(--active-inner);
                        --x: 17px;
                      }
                      input[type=checkbox].switch:disabled:not(:checked):after {
                        opacity: 0.6;
                      }

                      input[type=radio] {
                        border-radius: 50%;
                      }
                      input[type=radio]:after {
                        width: 19px;
                        height: 19px;
                        border-radius: 50%;
                        background: var(--active-inner);
                        opacity: 0;
                        transform: scale(var(--s, 0.7));
                      }
                      input[type=radio]:checked {
                        --s: .5;
                      }
                    }
                    .txt-label{
                        color: var(--lf-on-primary);
                        padding-left: 5px;
                        font-size: 200%;
                        position: relative;
                    }
                    .toggle-btn{
                        padding-left: 20px;
                    }
                    .ch2{
                        padding-top: 60px;
                    }
                    </style>
                <div class="profile-img position-relative me-3 mb-3 mb-lg-0 profile-logo profile-logo1">
                  @if(file_exists(base_path(findAvatar(Auth::user()->id))))
                  <img src="{{ url(findAvatar(Auth::user()->id)) }}" class="img-fluid rounded-pill avatar-100 bg-white" width="100" height="100" draggable="false" style="object-fit:cover;">
                  @elseif(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
                  <img src="{{ url("assets/linkstack/images/")."/".findFile('avatar') }}" class="img-fluid rounded-pill avatar-100 bg-white" width="100" height="100" draggable="false">
                  @else
                  <img src="{{ asset('assets/linkstack/images/favicon.svg') }}" class="img-fluid rounded-pill avatar-100 bg-white" width="100" height="100" draggable="false">
                  @endif
                  @if(file_exists(base_path(findAvatar(Auth::user()->id))))
                  <div class="upload-icone bg-primary">
                    <form method="POST" action="{{ route('delProfilePicture') }}" class="d-inline" onsubmit="return confirm('Remover a foto do perfil?')">@csrf<button type="submit" class="btn btn-link p-0" style="top:1px;position:relative;" title="Remover foto do perfil" aria-label="Remover foto do perfil"><i class="bi bi-trash-fill text-white"></i></button></form>
                    <input class="file-upload" type="file" accept="image/*">
                  </div>
                  @endif
                </div>
                <section class='text-gray-400'>
                <h3 class="mb-4 card-header"><i class="bi bi-file-earmark-break"> {{__('messages.My Profile')}}</i></h3>
                @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24">
                        <use xlink:href="#exclamation-triangle-fill"></use>
                    </svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                </div>
                @endif
                <div>

                <div></div>
                @foreach($pages as $page)
                <form action="{{ route('editPage') }}" enctype="multipart/form-data" method="post">
                    @csrf
                    @if($page->littlelink_name != '')
                    <div class="form-group col-lg-8">
                      <label for="customFile" class="form-label">{{__('messages.Profile Picture')}}</label>
                      <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" name="image" class="form-control" id="customFile">
                  </div>
                    @endif

                    <!--<div class="form-group col-lg-8">
                            <label for="field-pageDescription">Path name</label>
                            @<input type="text" class="form-control" name="pageName" value="{{ $page->littlelink_name ?? '' }}">
                          </div>-->

                    <div class="form-group col-lg-8">
                        <?php
                            $url = $_SERVER['REQUEST_URI'];
                             if( strpos( $url, "no_page_name" ) == true ) echo '<span style="color:var(--lf-danger); font-size:120%;">You do not have a Page URL</span>'; ?>
                        <br>
                        <label class="form-label">{{__('messages.Page URL')}}</label>
                        <div class="input-group mb-3 has-validation">
                          <span class="input-group-text" id="basic-addon3">{{str_replace(['http://', 'https://'], '', url(''))}}/@</span>
                          <input type="text" class="form-control" id="littlelink_name" name="littlelink_name" aria-describedby="littlelink_name" value="{{ old('littlelink_name', $page->littlelink_name ?? '') }}" required autofocus>
                        </div>
                        <script>const exceptionvar = @json($page->littlelink_name ?? '');</script>
                        @include('auth.url-validation')

                         <label style="margin-top:15px">{{__('messages.Display name')}}</label>
                        <div class="input-group">
                            {{-- <div class="input-group-prepend">
                                <div class="input-group-text">{{__('messages.Name')}}</div>
                            </div> --}}
                            <input type="text" class="form-control" name="name" value="{{ $page->name }}" required>
                        </div>
                    </div>

                    <div class="form-group col-lg-8">
                        <label>{{__('messages.Page Description')}}</label>
                        <textarea class="form-control @if(env('ALLOW_USER_HTML') === true) ckeditor @endif" name="pageDescription" rows="3" id="field-pageDescription">{{ $page->littlelink_description ?? '' }}</textarea>
                    </div>

                    @if(auth()->user()->verified_at !== null)
                        <div class="form-group col-lg-8">
                          <h5 style="margin-top:50px">Selo verificado</h5>
                          <p class="text-muted">Seu selo foi aprovado pelo administrador. Escolha se ele aparece na sua página pública.</p>
                          <div class="mb-3 form-check form-switch">
                            <input name="verification_visible" value="1" class="switch toggle-btn" type="checkbox" id="verification-visible"
                                   @checked((bool) auth()->user()->verification_visible) />
                            <label class="form-check-label" for="verification-visible">Exibir selo</label>
                          </div>
                        </div>
                    @endif
                    @endforeach

                    {{-- Container comum: não depende de aprovação do selo. --}}
                    <div class="form-group col-lg-8">
                    <div class="form-group col-lg-8">
                      <h5 style="margin-top:50px">{{__('messages.Show share button')}}</h5>
                      <p class="text-muted">{{__('messages.disablesharebutton')}}</p>
                        <div class="mb-3 form-check form-switch">
                          <input name="sharebtn" class="switch toggle-btn" type="checkbox" id="sharebtn" <?php if(UserData::getData(Auth::user()->id, 'disable-sharebtn') != "true"){echo 'checked';} ?> />
                          <label class="form-check-label" for="sharebtn">{{__('messages.Enable')}}</label>
                        </div>

                        <div class="form-group col-lg-8">
                          <h5 style="margin-top:50px">{{__('messages.Open links in new tab')}}</h5>
                          <p class="text-muted">{{__('messages.openlinksnewtab')}}</p>
                            <div class="mb-3 form-check form-switch">
                              <input name="tablinks" class="switch toggle-btn" type="checkbox" id="tablinks" <?php if(UserData::getData(Auth::user()->id, 'links-new-tab') != false){echo 'checked';} ?> />
                              <label class="form-check-label" for="tablinks">{{__('messages.Enable')}}</label>
                            </div>

                    <fieldset class="border rounded-3 p-3 mt-4" style="max-width:850px">
                      <legend class="fs-6 fw-bold">{{ __('messages.lf_privacy_stats') }}</legend>
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="search_noindex" id="lf-noindex" value="1"
                          @checked((bool) \App\Models\UserData::getData(auth()->id(), 'search_noindex'))>
                        <label class="form-check-label" for="lf-noindex">{{ __('messages.lf_noindex') }}</label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="public_visit_count" id="lf-visit-counter" value="1"
                          @checked((bool) \App\Models\UserData::getData(auth()->id(), 'public_visit_count'))>
                        <label class="form-check-label" for="lf-visit-counter">{{ __('messages.lf_public_counter') }}</label>
                      </div>
                    </fieldset>
                    <button id="submit-btn" type="submit" class="mt-3 ml-3 btn btn-primary">{{__('messages.Save')}}</button>
                </form>

                @if(env('ALLOW_USER_HTML') === true)
                <script src="{{ asset('assets/external-dependencies/ckeditor.js') }}"></script>
                <script>
                  ClassicEditor
                      .create(document.querySelector('.ckeditor'), {
                          toolbar: {
                              items: [
                                  'exportPDF', 'exportWord', '|',
                                  'findAndReplace', 'selectAll', '|',
                                  'heading', '|',
                                  'bold', 'italic', 'strikethrough', 'underline', 'code', 'subscript', 'superscript', 'removeFormat', '|',
                                  'bulletedList', 'numberedList', 'todoList', '|',
                                  'outdent', 'indent', '|',
                                  'undo', 'redo',
                                  'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                                  'alignment', '|',
                                  'link', 'blockQuote', '|',
                                  'specialCharacters', 'horizontalLine', '|',
                                  'textPartLanguage', '|',
                              ],
                              shouldNotGroupWhenFull: true
                          },
                          fontFamily: {
                              options: [
                                  'default',
                                  'Arial, Helvetica, sans-serif',
                                  'Courier New, Courier, monospace',
                                  'Georgia, serif',
                                  'Lucida Sans Unicode, Lucida Grande, sans-serif',
                                  'Tahoma, Geneva, sans-serif',
                                  'Times New Roman, Times, serif',
                                  'Trebuchet MS, Helvetica, sans-serif',
                                  'Verdana, Geneva, sans-serif'
                              ],
                              supportAllValues: true
                          },
                          fontSize: {
                              options: [10, 12, 14, 'default', 18, 20, 22],
                              supportAllValues: true
                          },
                          link: {
                              addTargetToExternalLinks: true, // Add this option to open external links in a new tab
                              defaultProtocol: 'http://',
                              decorators: {
                                  addTargetToExternalLinks: {
                                      mode: 'manual',
                                      label: 'Open in new tab',
                                      attributes: {
                                          target: '_blank',
                                          rel: 'noopener noreferrer'
                                      }
                                  }
                              }
                          }
                      })
                      .catch(error => {
                          console.error(error);
                      });
              </script>

                @endif
                </div>

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
</div>

@endsection
