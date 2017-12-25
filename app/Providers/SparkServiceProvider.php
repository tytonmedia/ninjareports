<?php

namespace App\Providers;

use Laravel\Spark\Spark;
use Laravel\Spark\Providers\AppServiceProvider as ServiceProvider;
use Carbon\Carbon;

class SparkServiceProvider extends ServiceProvider
{
    /**
     * Your application and company details.
     *
     * @var array
     */
    protected $details = [
        'vendor' => 'Your Company',
        'product' => 'Your Product',
        'street' => 'PO Box 111',
        'location' => 'Your Town, NY 12345',
        'phone' => '555-555-5555',
    ];

    /**
     * The address where customer support e-mails should be sent.
     *
     * @var string
     */
    protected $sendSupportEmailsTo = null;

    /**
     * All of the application developer e-mail addresses.
     *
     * @var array
     */
    protected $developers = [
        //
    ];

    /**
     * Indicates if the application will expose an API.
     *
     * @var bool
     */
    protected $usesApi = true;

    /**
     * Finish configuring Spark for the application.
     *
     * @return void
     */
    public function booted()
    {
        //Spark::useStripe()->noCardUpFront()->trialDays(10);

        /*Spark::freePlan()
            ->features([
                'First', 'Second', 'Third'
            ]);*/

        Spark::plan('Free Trial', 'free_trial')
            ->price(0.00)
            ->features([
                'First', 'Second', 'Third'
            ]);

        Spark::plan('Business', 'business')
            ->price(29.00)
            ->features([
                'First', 'Second', 'Third'
            ]);

        Spark::plan('Premium', 'premium')
            ->price(69.00)
            ->features([
                'First', 'Second', 'Third'
            ]);

        Spark::plan('White Label', 'white_label')
            ->price(129.00)
            ->features([
                'First', 'Second', 'Third'
            ]);


        Spark::validateUsersWith(function () {
            return [
                'first_name' => 'required|max:255',
                'last_name'  => 'required|max:255',
                'phone'      => 'required|max:20',
                'email'      => 'required|email|max:255|unique:users',
                'password'   => 'required|confirmed|min:6',
                'vat_id'     => 'max:50|vat_id',
                'terms'      => 'required|accepted',
            ];
        });

        Spark::createUsersWith(function ($request) {
            $user = Spark::user();

            $data = $request->all();

            $user->forceFill([
                'name' => $data['first_name']. ' ' .$data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => bcrypt($data['password']),
                'last_read_announcements_at' => Carbon::now(),
                'trial_ends_at' => Carbon::now()->addDays(Spark::trialDays()),
            ])->save();

            return $user;
        });
    }
}
