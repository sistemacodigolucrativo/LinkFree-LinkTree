@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-0 admin-overview-page">
<h1 class="h3 mb-3">{{__('messages.Nav Admin Overview')}}</h1>
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
