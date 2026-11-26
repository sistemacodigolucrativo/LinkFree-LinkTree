@extends('layouts.sidebar')

@section('content')

@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-platform-picker.css') }}?v={{ filemtime(base_path('assets/css/linkfree-platform-picker.css')) }}">
@endpush

<div class="conatiner-fluid content-inner mt-n5 py-0">
    <div class="row">

     <div class="col-lg-12">
        <div class="card rounded">
            <div class="card-body">
               <div class="row">
                   <div class="col-sm-12">

                    @push('sidebar-stylesheets')
                    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
                    @endpush

                    <section class='text-gray-400 link-editor'>


                        <div class='card-body'>
                            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
@if(auth()->user()->role === 'user')
 <p class="small text-muted">{{ __('messages.lf_link_usage', ['count' => \App\Services\UserLinkLimit::count(auth()->id()), 'max' => \App\Services\UserLinkLimit::FREE_LIMIT]) }}</p>
@endif
<form action="{{ route('addLink') }}" method="post" id="my-form">
                                @method('POST')
                                @csrf
                                <input type='hidden' name='linkid' value="{{ $LinkID }}" />

                                <div class="form-group col-lg-8 flex justify-around">
                                    <div class="btn-group shadow m-2">
                                        <a id="btnLinkType" class="btn btn-primary rounded-pill" href="{{ route('showBlocks') }}{{ $LinkID ? '?edit='.$LinkID : '' }}" title="{{__('messages.Click to change link blocks')}}">
                                        {{ $selectedLinkTypeTitle }} <i class="bi bi-grid-3x3-gap ms-2" aria-hidden="true"></i></a>{{infoIcon(__('messages.Click for a list of available link blocks'))}}

                                        <input type='hidden' name='typename' value='{{$typename}}'>

                                    </div>
                                </div>

                                <div id='link_params' class='col-lg-8'></div>

                                <div class="d-flex flex-wrap align-items-center gap-2 pt-4 link-editor-actions">
                                    <a class="btn btn-danger" href="{{ url('studio/links') }}">{{__('messages.Cancel')}}</a>
                                    <button type="submit" onclick="this.form.querySelector('input[name=param]')?.remove()" class="btn btn-primary">{{__('messages.Save')}}</button>
                                    <button type="button" class="btn btn-soft-primary" onclick="submitFormWithParam('add_more')">{{__('messages.Save and Add More')}}</button>
                                    <script>
                                        function submitFormWithParam(paramValue) {
                                            const form = document.getElementById('my-form');
                                            if (!form.reportValidity()) return;
                                            let field = form.querySelector('input[name="param"]');
                                            if (!field) {
                                                field = document.createElement('input');
                                                field.type = 'hidden'; field.name = 'param'; form.appendChild(field);
                                            }
                                            field.value = paramValue;
                                            form.requestSubmit();
                                        }
                                        </script>
                                </div>

                            </form>
                        </div>
                    </section>
                    <br><br>



                   </div>
               </div>
            </div>
         </div>
        </div>

      </div>
    </div>

<style>
.link-editor,.link-editor #link_params{min-width:0}
.link-editor-actions>*{max-width:100%;white-space:normal;overflow-wrap:anywhere}
.link-editor #link_params input,.link-editor #link_params select,.link-editor #link_params textarea{max-width:100%;min-width:0}
@media(max-width:575px){.link-editor>.card-body{padding-inline:0}.link-editor-actions>*{flex:1 1 100%}.link-editor #btnLinkType{white-space:normal}.link-editor #link_params .row{margin-inline:0}}
</style>
@endsection

@push("sidebar-scripts")
<script src="{{ asset('assets/js/linkfree-platform-picker.js') }}?v={{ filemtime(base_path('assets/js/linkfree-platform-picker.js')) }}"></script>
<script>
$(function() {
    LoadLinkTypeParams($("input[name='typename']").val() , $("input[name=linkid]").val());

    function LoadLinkTypeParams($TypeId, $LinkId) {
        var baseURL = <?php echo "\"" . url('') . "\""; ?>;
        $("#link_params").html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>').load(baseURL + `/studio/linkparamform_part/${$TypeId}/${$LinkId}`, function(response, status) {
            if (status === 'success' && window.LinkFreePlatformPicker) window.LinkFreePlatformPicker.init(this);
        });
        setTimeout(function() { document.dispatchEvent(new Event('contentLoaded')); }, 300);
    }
});
</script>
@endpush
