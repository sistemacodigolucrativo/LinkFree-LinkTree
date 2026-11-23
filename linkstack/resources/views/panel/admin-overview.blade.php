@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-0 admin-overview-page">
<h1 class="h3 mb-3">{{__('messages.Nav Admin Overview')}}</h1>
<section class="card rounded-4 mb-3" aria-labelledby="lf-admin-shortcuts-title">
 <div class="card-body p-3 p-md-4">
  <h2 class="h5 mb-2" id="lf-admin-shortcuts-title">{{ __('messages.lf_admin_shortcuts') }}</h2>
  <p class="small text-muted mb-3">{{ __('messages.lf_admin_existing_help') }}</p>
  @php
    // Somente rotas existentes; selos e denúncias seguem pendentes.
    $sections = [
      __('messages.lf_admin_users') => [
        ['route' => 'showUsers', 'icon' => 'bi-people', 'label' => __('messages.Manage Users')],
        ['route' => 'createNewUser', 'icon' => 'bi-person-plus', 'label' => __('messages.Nav Admin New User')],
         ['route' => 'admin.referrals', 'icon' => 'bi-people', 'label' => 'Indicações'],
      ],
      __('messages.lf_admin_site') => [
        ['route' => 'showSite', 'icon' => 'bi-globe', 'label' => __('messages.Site Customization')],
        ['route' => 'showSitePage', 'icon' => 'bi-file-earmark-text', 'label' => __('messages.Footer Pages')],
      ],
      __('messages.lf_admin_themes') => [
        ['route' => 'showThemes', 'icon' => 'bi-brush', 'label' => __('messages.Nav Admin Theme Manager')],
        ['route' => 'githubThemes', 'icon' => 'bi-github', 'label' => __('messages.Nav Admin GitHub Themes')],
      ],
      __('messages.lf_admin_system') => [
        ['route' => 'showConfig', 'icon' => 'bi-sliders', 'label' => __('messages.Config')],
        ['route' => 'showBackups', 'icon' => 'bi-database', 'label' => __('messages.Nav Admin Backups')],
      ],
    ];
  @endphp
  <div class="row g-3">
   @foreach($sections as $title => $items)
   <div class="col-12 col-md-6 col-xl-3">
    <div class="lf-admin-section border rounded-3 p-3 h-100">
     <h3 class="h6 fw-bold">{{ $title }}</h3>
     <div class="d-grid gap-2">
      @foreach($items as $item)
       <a class="btn btn-outline-primary text-start" href="{{ route($item['route']) }}">
        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i> {{ $item['label'] }}
       </a>
      @endforeach
     </div>
    </div>
   </div>
   @endforeach
  </div>
  <p class="small text-muted mb-0 mt-3">Pendências administrativas restantes: solicitações de selo verificado e gerenciamento de denúncias.</p>
 </div>
</section>
<style>
.admin-overview-page .lf-admin-section{min-width:0;background:var(--lf-surface)}
.admin-overview-page .lf-admin-section .btn{white-space:normal;overflow-wrap:anywhere;min-height:42px}
.admin-overview-page .lf-admin-section .btn:focus-visible{outline:3px solid var(--lf-focus);outline-offset:2px}
</style>
@if(auth()->user()->role === 'admin' && !config('linkstack.single_user_mode'))
<div class="row">
       <div class="col-lg-12">
          <div class="card   rounded">
             <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">  



        <!-- Section: Design Block -->
        <section class="mb-3 text-gray-800 text-center p-4 w-full">
            <div class='font-weight-bold text-left h3'>{{__('messages.Site statistics:')}}</div><br>
            <div class="d-flex flex-wrap justify-content-around">

                <div class="p-2">
                    <h3 class="text-primary"><strong><i class="bi bi-share-fill"> {{ $siteLinks }} </i></strong></h3>
                    <span class="text-muted">{{__('messages.Total links')}}</span>
                </div>

                <div class="p-2">
                    <h3 class="text-primary"><strong><i class="bi bi-eye-fill"> {{ $siteClicks }} </i></strong></h3>
                    <span class="text-muted">{{__('messages.Total clicks')}}</span>
                </div>

                <div class="p-2">
                    <h3 class="text-primary"><strong><i class="bi bi-person-fill"> {{ $userNumber }}</i></strong></h3>
                    <span class="text-muted">{{__('messages.Total users')}}</span>
                </div>

            </div>
        </section>

                    </div>
                </div>
             </div>
          </div>
       </div>       
       
       <div class="col-lg-12">
        <div class="card   rounded">
           <div class="card-body">
              <div class="row">
                  <div class="col-sm-12">  

      <!-- Section: Design Block -->
      <section class="mb-3 text-gray-800 text-center p-4 w-full">
          <div class='font-weight-bold text-left h3'>{{__('messages.Registrations:')}}</div><br>
          <div class="d-flex flex-wrap justify-content-around">

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $lastMonthCount }}</strong></h3>
                  <span class="text-muted">{{__('messages.Last 30 days')}}</span>
              </div>

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $lastWeekCount }}</strong></h3>
                  <span class="text-muted">{{__('messages.Last 7 days')}}</span>
              </div>

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $last24HrsCount }}</i></strong></h3>
                  <span class="text-muted">{{__('messages.Last 24 hours')}}</span>
              </div>

          </div>
      </section>

                  </div>
              </div>
           </div>
        </div>
     </div>   

     <div class="col-lg-12">
        <div class="card   rounded">
           <div class="card-body">
              <div class="row">
                  <div class="col-sm-12">  


      <!-- Section: Design Block -->
      <section class="mb-3 text-gray-800 text-center p-4 w-full">
          <div class='font-weight-bold text-left h3'>{{__('messages.Active users:')}}</div><br>
          <div class="d-flex flex-wrap justify-content-around">

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $updatedLast30DaysCount }}</strong></h3>
                  <span class="text-muted">{{__('messages.Last 30 days')}}</span>
              </div>

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $updatedLast7DaysCount }}</strong></h3>
                  <span class="text-muted">{{__('messages.Last 7 days')}}</span>
              </div>

              <div class="p-2">
                  <h3 class="text-primary"><strong> {{ $updatedLast24HrsCount }}</i></strong></h3>
                  <span class="text-muted">{{__('messages.Last 24 hours')}}</span>
              </div>

          </div>
      </section>
                  </div>
              </div>
           </div>
        </div>
     </div>   

</div>
@endif
</div>
@endsection
