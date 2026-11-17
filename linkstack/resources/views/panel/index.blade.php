@extends('layouts.sidebar')


@section('content')
<style>
.dashboard-slim .dashboard-main-section{padding:12px!important;margin-bottom:0!important}
.dashboard-slim .dashboard-stats{display:flex;flex-wrap:wrap;gap:5px 16px;align-items:center}
.dashboard-slim .dashboard-stats>.p-2{padding:5px 8px!important;margin:0!important}
.dashboard-slim .top-links-heading{margin:12px 0 8px}
.dashboard-slim .top-links-list{display:flex;flex-direction:column;gap:8px;max-width:100%;min-width:0}
.dashboard-slim .top-links-list>li.top-links-slim-item{
 display:flex;align-items:center;flex-wrap:nowrap;gap:14px;
 width:100%;min-width:0;min-height:48px;padding:8px 12px;
 overflow-x:auto;overflow-y:hidden;overscroll-behavior-inline:contain;
 -webkit-overflow-scrolling:touch;white-space:nowrap;
 border:1px solid var(--lf-border);border-inline-start:4px solid var(--lf-chip);
 border-radius:10px;background:var(--lf-raised);scrollbar-width:thin;
}
.dashboard-slim .top-links-list .top-link-info{
 display:inline-flex;align-items:center;gap:12px;flex:1 0 auto;
 min-width:max-content;max-width:none;white-space:nowrap
}
.dashboard-slim .top-links-list .top-link-title{font-weight:700;white-space:nowrap}
.dashboard-slim .top-links-list .top-link-url{font-size:.86rem;opacity:.8;white-space:nowrap}
.dashboard-slim .top-links-list .top-link-clicks{display:inline-flex;align-items:center;flex:0 0 auto;white-space:nowrap;margin-inline-start:auto}
.dashboard-slim .top-links-list>li.top-links-slim-item:focus-visible{outline:3px solid var(--lf-focus);outline-offset:2px}
@media(max-width:575px){
 .dashboard-slim .dashboard-main-section{padding:8px!important}
 .dashboard-slim .top-links-list>li.top-links-slim-item{padding:7px 9px;min-height:46px}
 .dashboard-slim .top-links-list .top-link-info{gap:9px}
}
</style>
<div class="conatiner-fluid content-inner mt-n5 py-0 dashboard-slim">
    <div class="row">   
        
     <div class="col-lg-12">
        <div class="card   rounded">
            <div class="card-body">
               <div class="row">
                   <div class="col-sm-12">  

                    <h3 class="mb-4"><i class="bi bi-menu-up"></i> {{__('messages.Dashboard')}}</h3>
                    
                            <section class="dashboard-main-section mb-3 text-center p-2 w-full">
                                <div class="dashboard-stats d-flex">
                    
                                    <div class='p-2 h6'><i class="bi bi-link"></i> {{__('messages.Total Links:')}} <span class='text-primary'>{{ $links }} </span></div>
                    
                                    <div class='p-2 h6'><i class="bi bi-eye"></i> {{__('messages.Link Clicks:')}} <span class='text-primary'>{{ $clicks }}</span></div>
                                </div>
                                <div class='text-center w-100'>
                                    <a href="{{ url('/studio/links') }}">{{__('messages.View/Edit Links')}}</a>
                    
                                </div>
                                <div class='w-100 text-left'>
                                    <h6 class="top-links-heading"><i class="bi bi-sort-up"></i> {{__('messages.Top Links:')}}</h6>
                    
                                                    @php $i = 0; @endphp
                    

                                                    <div class="bd-example" >
                                                        <ol class="list-group top-links-list" style="text-align:left;">
                                                          @if($toplinks == "[]")
                                                          <div class="container">
                                                            <div class="row justify-content-center mt-3">
                                                              <div class="col-6 text-center">
                                                                <p class="p-2">{{__('messages.You haven’t added any links yet')}}</p>
                                                              </div>
                                                            </div>
                                                          </div>                                                        
                                                          @else
                                                          @foreach($toplinks as $link)
                                                            @php $linkName = str_replace('default ','',$link->name) @endphp
                                                            @php  $i++; @endphp
                                                      
                                                            @if($link->name !== "phone" && $link->name !== 'heading' && $link->button_id !== 96)
                                                              <li class="list-group-item top-links-slim-item" tabindex="0" aria-label="{{ $link->title }} — {{ $link->click_number }} {{__('messages.clicks')}}">
  <div class="top-link-info">
    <span class="top-link-title">{{ $link->title }}</span>
    <span class="top-link-url">{{ $link->link }}</span>
  </div>
  <span class="badge bg-primary rounded-pill p-2 top-link-clicks">{{ $link->click_number }} {{__('messages.clicks')}}</span>
</li>
                                                            @endif
                                                          @endforeach
                                                          @endif
                                                        </ol>
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
@endsection
