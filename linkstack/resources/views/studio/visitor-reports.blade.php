@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-2">
 <section class="card rounded-4">
  <div class="card-body p-3 p-md-4">
   <p class="text-muted">{{ __('messages.lf_visitor_note') }}</p>
   <p class="fw-semibold">{{ __('messages.lf_visitor_last30') }} · {{ __('messages.lf_public_visits_label', ['count' => number_format($visitsTotal, 0, ',', '.')]) }}</p>
   <div class="table-responsive">
    <table class="table table-hover align-middle">
     <caption>{{ __('messages.lf_visitor_last30') }}</caption>
     <thead><tr><th scope="col">{{ __('messages.lf_visitor_source') }}</th><th scope="col" class="text-end">{{ __('messages.lf_visitor_count') }}</th></tr></thead>
     <tbody>
      @foreach($sources as $source => $count)
      <tr><th scope="row">{{ ucfirst(str_replace(['utm:', '_'], ['Campanha: ', ' '], $source)) }}</th><td class="text-end">{{ number_format($count, 0, ',', '.') }}</td></tr>
      @endforeach
     </tbody>
    </table>
   </div>
  </div>
 </section>
</div>
@endsection
