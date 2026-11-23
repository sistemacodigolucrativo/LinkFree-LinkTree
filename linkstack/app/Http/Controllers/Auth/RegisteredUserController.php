<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationVip;
use App\Services\ConsentReceipt;
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
            return response()->json(['valid' => false]);
        }
    
        return response()->json(['valid' => true]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'littlelink_name' => 'required|string|max:50|unique:users|regex:/^[\p{L}0-9-_]+$/u',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'accept_terms' => 'accepted',
        ], ['accept_terms.accepted' => __('messages.lf_accept_required')]);

        $name = $request->input('name');

        if(env('MANUAL_USER_VERIFICATION') == true){
            $block = 'yes';
        } else {
            $block = 'no';
        }

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
            return $created;
        });
        Auth::login($user);


            $registeredName = $request->name;
            $email = $request->email;
            
            if(env('REGISTER_AUTH') == 'verified'){
                if(env('MANUAL_USER_VERIFICATION') == true){
                try {
                Mail::send('auth.user-confirmation', ['user' => $registeredName, 'email' => $email], function ($message) {
                    $message->to(env('ADMIN_EMAIL'))
                            ->subject('New user registration');
                });
            } catch (\Exception $e) {}
        }
    
            try {
            $request->user()->sendEmailVerificationNotification();
            } catch (\Exception $e) {}
        }

        event(new Registered($user));

        return redirect(url('dashboard'));
    }
}
