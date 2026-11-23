<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SocialAccount;
use App\Services\RegistrationVip;
use App\Services\ConsentReceipt;
use Illuminate\Support\Facades\DB;

class SocialLoginController extends Controller
{
    public function consent(Request $request, String $provider)
    {
        $request->validate(['accept_terms' => 'accepted'], [
            'accept_terms.accepted' => __('messages.lf_accept_required'),
        ]);
        if (!in_array($provider, ['facebook', 'twitter', 'google', 'github'], true)) abort(404);
        $request->session()->put('lf_social_consent', ['at' => time(), 'accepted' => true]);
        return response()->json(['redirect' => route('social.redirect', ['provider' => $provider])]);
    }

    public function redirectToProvider(String $provider)
    {
        return \Socialite::driver($provider)->redirect();
    }

    public function providerCallback(String $provider)
    {
        try {
            $social_user = \Socialite::driver($provider)->user();

            // First Find Social Account
            $account = SocialAccount::where([
                'provider_name' => $provider,
                'provider_id' => $social_user->getId()
            ])->first();

            // If Social Account Exist then Find User and Login
            if ($account) {
                auth()->login($account->user);
                return redirect('/studio/index');
            }

            // Find User
            $user = User::where([
                'email' => $social_user->getEmail()
            ])->first();

            // If User not found, then create new user
            if (!$user) {
                $consent = session('lf_social_consent');
                if (!is_array($consent) || empty($consent['accepted']) ||
                    time() - (int) ($consent['at'] ?? 0) > 600) {
                    return redirect()->route('register')->withErrors([
                        'accept_terms' => __('messages.lf_accept_required'),
                    ]);
                }
                $user = DB::transaction(function () use ($social_user) {
                    $created = User::create([
                        'email' => $social_user->getEmail(),
                        'name' => $social_user->getName(),
                        'image' => $social_user->getAvatar(),
                        'littlelink_name' => $social_user->getNickname(),
                        'email_verified_at' => now(),
                    ]);
                    $created->role = RegistrationVip::roleForNewUser();
                    $created->save();
                    ConsentReceipt::record($created, 'social');
                    return $created;
                });
                session()->forget('lf_social_consent');
            }

            // Create Social Accounts
            $user->socialAccounts()->create([
                'provider_id' => $social_user->getId(),
                'provider_name' => $provider
            ]);

            // Login
            auth()->login($user);
            return redirect('/studio/index');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors($e->getMessage());
        }
    }
}



