<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="{{ \App\Services\PublicSeo::robots() }}">
<title>{{ __('reviews.public_title') }} — {{ config('app.name') }}</title>
<meta name="description" content="{{ __('reviews.public_intro', ['appName' => config('app.name')]) }}">
<link rel="canonical" href="{{ route('public.platform-reviews') }}">
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-platform-reviews.css') }}?v={{ filemtime(base_path('assets/css/linkfree-platform-reviews.css')) }}">
</head>
<body class="lf-review-public">
<a class="lf-review-skip" href="#depoimentos">{{ __('reviews.public_title') }}</a>
<main class="lf-review-page" id="depoimentos">
 <div class="lf-review-shell">
   <div class="lf-review-kicker">{{ config('app.name') }}</div>
   <h1>{{ __('reviews.public_title') }}</h1>
   <p class="lf-review-lead">{{ __('reviews.public_intro', ['appName' => config('app.name')]) }}</p>
   <div class="lf-public-review-grid">
   @forelse($reviews as $review)
     <article class="lf-review-quote">
       <div class="lf-review-stars-read" aria-label="{{ __('reviews.stars', ['count' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<span aria-hidden="true">{{ str_repeat('☆', 5 - $review->rating) }}</span></div>
       @if($review->testimonial)<blockquote>{{ $review->testimonial }}</blockquote>@endif
       <p>{{ __('reviews.anonymous') }}</p>
     </article>
   @empty
     <p class="lf-review-empty">{{ __('reviews.no_public_reviews') }}</p>
   @endforelse
   </div>
   <div class="lf-public-review-actions">
     @auth<a class="lf-review-submit" href="{{ route('studio.platform-review') }}">{{ __('reviews.write_review') }}</a>@endauth
     <a class="lf-review-back" href="{{ url('/') }}">{{ __('reviews.back_home') }}</a>
   </div>
 </div>
</main>
</body>
</html>
