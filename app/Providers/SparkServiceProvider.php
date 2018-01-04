<?php

namespace App\Providers;

use Laravel\Spark\Providers\AppServiceProvider as ServiceProvider;
use Laravel\Spark\Spark;

class SparkServiceProvider extends ServiceProvider
{
    /**
     * Your application and company details.
     *
     * @var array
     */
    protected $details = [
        'vendor' => 'Ninja Reports',
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
    protected $usesApi = false;

    /**
     * Finish configuring Spark for the application.
     *
     * @return void
     */
    public function booted()
    {
        Spark::plan('Free Trial', 'free_trial')
            ->price(0.00)
            ->features([
                'First', 'Second', 'Third',
            ]);

        Spark::plan('Business', 'business')
            ->price(29.00)
            ->features([
                'First', 'Second', 'Third',
            ]);

        Spark::plan('Premium', 'premium')
            ->price(69.00)
            ->features([
                'First', 'Second', 'Third',
            ]);

        Spark::plan('White Label', 'white_label')
            ->price(129.00)
            ->features([
                'First', 'Second', 'Third',
            ]);
    }
}
