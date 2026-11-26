@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
 <div class="card rounded-4"><div class="card-body p-3 p-md-4">
  <h1 class="h3">{{ __('reviews.moderation_title') }}</h1>
  <p class="text-muted">{{ __('reviews.privacy') }}</p>
  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
  <div class="table-responsive" tabindex="0" role="region" aria-label="{{ __('reviews.moderation_title') }}">
   <table class="table table-striped align-middle">
    <thead><tr>
      <th scope="col">{{ __('reviews.member') }}</th>
      <th scope="col">{{ __('reviews.rate') }}</th>
      <th scope="col">{{ __('reviews.testimonial') }}</th>
      <th scope="col">{{ __('reviews.status') }}</th>
      <th scope="col">{{ __('reviews.actions') }}</th>
    </tr></thead>
    <tbody>
    @forelse($reviews as $review)
    <tr>
      <td><strong>{{ $review->member_name }}</strong><small class="d-block">{{ $review->member_email }}</small></td>
      <td>{{ $review->rating }} ★</td>
      <td style="white-space:pre-wrap;min-width:220px;max-width:420px">{{ $review->testimonial }}</td>
      <td>{{ __('reviews.review_'.$review->status) }}<small class="d-block">{{ $review->created_at }}</small></td>
      <td>
        @if($review->status === 'pending')
        <form method="post" action="{{ route('admin.platform-reviews.moderate', ['id' => $review->id]) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="btn btn-sm btn-success mb-2" type="submit">{{ __('reviews.approve') }}</button></form>
        <form method="post" action="{{ route('admin.platform-reviews.moderate', ['id' => $review->id]) }}">@csrf<input type="hidden" name="decision" value="reject"><button class="btn btn-sm btn-outline-danger" type="submit">{{ __('reviews.reject') }}</button></form>
        @elseif($review->status === 'approved')
        <form method="post" action="{{ route('admin.platform-reviews.moderate', ['id' => $review->id]) }}">@csrf<input type="hidden" name="decision" value="hide"><button class="btn btn-sm btn-outline-danger" type="submit">{{ __('reviews.hide') }}</button></form>
        @else — @endif
      </td>
    </tr>
    @empty<tr><td colspan="5">{{ __('reviews.no_pending') }}</td></tr>
    @endforelse
    </tbody>
   </table>
  </div>
  <nav aria-label="{{ __('reviews.moderation_title') }}">{{ $reviews->links() }}</nav>
 </div></div>
</div>
@endsection
