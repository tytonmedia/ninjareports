<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Validator;
use Session;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
     */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home?welcome=1';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'timezone' => 'required',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'timezone' => $data['timezone'],
        ]);
        echo $user->current_billing_plan; exit;
          $new_user_substitutions = [
             '%name%' => $user->name,
            '%email%' => $user->email,
            '%package%' => $user->current_billing_plan,
        ];
        $welcome_email_substitutions = [
            '%app_link%' => env('APP_URL'),
            '%app_name%' => env('APP_NAME'),
            '%email%' => $user->email,
            '%package%' => $user->current_billing_plan,
            '%year%' => date('Y'),
        ];
        sendMail($user->email, 'Welcome To Ninja Reports!', '66424c1c-aa6b-4daa-a031-2edc29ea620a', $welcome_email_substitutions);
        sendMail('alerts@tytonmedia.com','New Ninja Reports User','a48e9f9c-19e3-4b80-b0ea-2b97e8a46db0', $new_user_substitutions);
    }
}
