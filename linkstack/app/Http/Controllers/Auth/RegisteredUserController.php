<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationVip;
use App\Services\ConsentReceipt;
use App\Services\ReferralService;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{

    public function create()
    {
        return view('auth.register');
    }

    public function validateHandle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'littlelink_name' => 'required|string|max:50|unique:users|regex:/^[\p{L}0-9-_]+$/u',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['valid' => false, 'reason' => $validator->errors()->has('littlelink_name') && isset($validator->failed()['littlelink_name']['Unique']) ? 'unique' : 'invalid']);
        }
    
        return response()->json(['valid' => true]);
    }

    public function store(Request $request)
    {
        $labels = [
            'name' => __('messages.Display Name'), 'littlelink_name' => __('messages.Page URL'),
            'email' => __('messages.Email'), 'password' => __('messages.Password'),
        ];
        $messages = ['accept_terms.accepted' => __('messages.lf_accept_required')];
        foreach ($labels as $field => $label) {
            foreach (['required', 'string', 'max', 'min', 'regex', 'email'] as $rule) {
                $messages[$field.'.'.$rule] = __('registration.invalid', ['field' => $label]);
            }
        }
        foreach (['littlelink_name', 'email'] as $field) {
            $messages[$field.'.unique'] = __('registration.unique', ['field' => $labels[$field]]);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'littlelink_name' => 'required|string|max:50|unique:users|regex:/^[\p{L}0-9-_]+$/u',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'accept_terms' => 'accepted',
        ], $messages);

        $name = $request->input('name');

        if(env('MANUAL_USER_VERIFICATION') == true){
            $block = 'yes';
        } else {
            $block = 'no';
        }

        try {
            $user = DB::transaction(function () use ($request, $block) {
                $created = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'littlelink_name' => $request->littlelink_name,
                    'password' => Hash::make($request->password),
                ]);
                $created->role = RegistrationVip::roleForNewUser();
                $created->block = $block;
                $created->save();
                ConsentReceipt::record($created, 'email');
                ReferralService::record($created, $request->session());
                return $created;
            });
        } catch (\Throwable $exception) {
            \Log::warning('Registration failed', ['exception_type' => get_class($exception)]);
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['registration' => __('registration.failure')]);
        }
        Auth::login($user);
        $request->session()->forget(ReferralService::SESSION_KEY);


        if (env('REGISTER_AUTH') === 'verified' && env('MANUAL_USER_VERIFICATION') == true) {
            try {
                Mail::send('auth.user-confirmation', ['user' => $request->name, 'email' => $request->email], function ($message) {
                    $message->to(env('ADMIN_EMAIL'))->subject('New user registration');
                });
            } catch (\Throwable $exception) {
                \Log::warning('Registration administrator notification failed', ['exception_type' => get_class($exception)]);
            }
        }

        try {
            // The existing Registered listener sends the verification notification once.
            event(new Registered($user));
        } catch (\Throwable $exception) {
            \Log::warning('Registration verification notification failed', ['exception_type' => get_class($exception)]);
            if (env('REGISTER_AUTH') === 'verified') {
                return redirect()->route('verification.notice')->with('verification_delivery_failed', true);
            }
        }

        return redirect(url('dashboard'));
    }
}
