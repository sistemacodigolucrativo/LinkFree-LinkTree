@extends('linkstack.layout')

@section('content')
    @push('linkstack-head')
        @include('linkstack.modules.meta')
        @include('linkstack.modules.assets')
    @endpush

    @push('linkstack-head-end')
        @foreach($information as $info)
            @include('linkstack.modules.theme')
        @endforeach
    @endpush

    @push('linkstack-body-start')
        @include('linkstack.modules.admin-bar')
        @include('linkstack.modules.share-button')
        @include('linkstack.modules.report-icon')
    @endpush

    @push('linkstack-content')
        @foreach($information as $info)
            @include('linkstack.elements.avatar')
            @include('linkstack.elements.heading')
            @include('linkstack.elements.bio')
        @if(!empty($showVisitCount))
          <p class="fadein dynamic-contrast" role="status" style="font-size:.85rem;line-height:1.6;text-align:center">
            <i class="bi bi-eye" aria-hidden="true"></i> {{ __('messages.lf_public_visits_label', ['count' => number_format((int) ($visitCount ?? 0), 0, ',', '.')]) }}
          </p>
        @endif
        @endforeach
        @include('linkstack.elements.icons')
        @include('linkstack.elements.buttons')
        @yield('content')
        @include('linkstack.modules.footer')
    @endpush
@endsection