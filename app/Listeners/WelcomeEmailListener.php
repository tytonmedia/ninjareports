<?php

namespace App\Listeners;

use Laravel\Spark\Events\Auth\UserRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeEmailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $user = auth()->user();
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
