@extends('layouts.sidebar')

@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-platform-reviews.css') }}?v={{ filemtime(base_path('assets/css/linkfree-platform-reviews.css')) }}">
@endpush

@section('content')
<div class="lf-review-page">
  <div class="lf-review-shell">
    <div class="lf-review-kicker">{{ __('reviews.eyebrow') }}</div>
    <h1>{{ __('reviews.heading') }}</h1>
    <p class="lf-review-lead">{{ __('reviews.intro', ['appName' => config('app.name')]) }}</p>
    @if(session('success'))<div class="lf-review-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
      <div class="lf-review-errors" role="alert">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </div>
    @endif

    @if($existingReview)
      <div class="lf-review-existing">
        <h2>{{ __('reviews.review_status') }}</h2>
        <p><strong>{{ __('reviews.review_'.$existingReview->status) }}</strong> · {{ $existingReview->rating }} ★</p>
        @if($existingReview->testimonial)<blockquote>{{ $existingReview->testimonial }}</blockquote>@endif
        <form method="post" action="{{ route('studio.platform-review.withdraw') }}">
          @csrf
          <button type="submit" class="lf-review-quiet"
                  onclick="return confirm(@json(__('reviews.withdraw_confirm')))">{{ __('reviews.withdraw') }}</button>
        </form>
      </div>
    @else
      <form action="{{ route('studio.platform-review.store') }}" method="post" class="lf-review-form">
        @csrf
        <fieldset class="lf-review-fieldset">
          <legend>{{ __('reviews.rate') }}</legend>
          <div class="lf-review-stars" aria-label="{{ __('reviews.rate') }}">
            @for($i = 1; $i <= 5; $i++)
              <input class="lf-rating-input" type="radio" name="rating" id="lf-rating-{{ $i }}" value="{{ $i }}" {{ (int) old('rating') === $i ? 'checked' : '' }} required>
              <label for="lf-rating-{{ $i }}" title="{{ __('reviews.stars', ['count' => $i]) }}" aria-label="{{ __('reviews.stars', ['count' => $i]) }}">★</label>
            @endfor
          </div>
        </fieldset>
        <label class="lf-review-label" for="lf-review-text">{{ __('reviews.testimonial') }}</label>
        <textarea id="lf-review-text" name="testimonial" maxlength="1200" rows="5" placeholder="{{ __('reviews.placeholder') }}">{{ old('testimonial') }}</textarea>
        <p class="lf-review-privacy">{{ __('reviews.privacy') }}</p>
        <label class="lf-review-consent">
          <input type="checkbox" name="publication_consent" value="1" {{ old('publication_consent') ? 'checked' : '' }} required>
          <span>{{ __('reviews.consent', ['appName' => config('app.name')]) }}</span>
        </label>
        <div class="lf-review-honeypot" aria-hidden="true"><label for="lf-review-website">Website</label><input type="text" id="lf-review-website" name="website" tabindex="-1" autocomplete="off"></div>
        <button type="submit" class="lf-review-submit">{{ __('reviews.submit') }}</button>
      </form>
    @endif

    <div class="lf-review-thanks">{{ __('reviews.thanks', ['appName' => config('app.name')]) }}</div>
    <aside class="lf-review-contribution">
      <h2>{{ __('reviews.contribution_title') }}</h2>
      <p>{{ __('reviews.contribution_text') }}</p>
      @if($contributionUrl)
        <a href="{{ $contributionUrl }}" target="_blank" rel="noopener noreferrer nofollow">{{ __('reviews.contribution_link') }}</a>
      @else
        <p class="lf-review-pending">{{ __('reviews.contribution_pending') }}</p>
      @endif
    </aside>
    <p><a class="lf-review-back" href="{{ route('public.platform-reviews') }}">{{ __('reviews.public_title') }}</a></p>
  </div>
</div>
<script>
(() => {
  const area = document.querySelector('.lf-review-stars');
  if (!area) return;
  const radios = [...area.querySelectorAll('input[type="radio"]')];
  const paint = () => {
    const selected = Number(radios.find(r => r.checked)?.value || 0);
    radios.forEach(r => r.nextElementSibling.classList.toggle('lf-selected', Number(r.value) <= selected));
  };
  radios.forEach(r => r.addEventListener('change', paint));
  paint();
})();
</script>
@endsection
