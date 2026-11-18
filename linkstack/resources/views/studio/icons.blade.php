@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
<style>
.studio-icons-page .page-icons-card{width:100%;max-width:100%;min-width:0}
.studio-icons-page .page-icons-card .card-body{min-width:0}
.studio-icons-page .page-icons-card .form-group{width:100%;max-width:760px;min-width:0}
.studio-icons-page .page-icons-card .input-group{display:flex;flex-wrap:nowrap;min-width:0}
.studio-icons-page .page-icons-card .input-group input{flex:1 1 auto;min-width:0}
.studio-icons-page .page-icons-card .input-group .btn{flex:0 0 auto}
@media(max-width:575px){.studio-icons-page .page-icons-card .card-body{padding-inline:12px!important}}
</style>
@endpush
@section('content')
<div class="conatiner-fluid content-inner mt-n5 py-0 studio-links-page studio-icons-page">
<div class="row"><div class="col-12">
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
                                </div>
                            </form>


                            </div>
                            </section>
</div></div>
</div>
@endsection
