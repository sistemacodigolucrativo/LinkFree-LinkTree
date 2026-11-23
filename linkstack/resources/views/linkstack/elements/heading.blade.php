<!-- Your Name -->
<h1 class="fadein dynamic-contrast">{{ $info->name }}@if(!empty($userinfo->verified_at) && (bool) $userinfo->verification_visible && theme('disable_verification_badge') != "true" && env('HIDE_VERIFICATION_CHECKMARK') != true)<span title="{{ __('messages.Verified user') }}" aria-label="{{ __('messages.Verified user') }}">@include('components.verify-svg')</span>@endif</h1>
