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
                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '15 Reports/month',
            ]);

         Spark::plan('Personal', 'personal')
            ->price(10.00)
            ->features([
                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '50 Reports/month',
            ]);    

        Spark::plan('Business', 'business')
            ->price(50.00)
            ->features([
                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '300 Reports/month', 'Support', 'PDF & CSV Attachments',
            ]);

        Spark::plan('Premium', 'premium')
            ->price(100.00)
            ->features([
                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '800 Reports/month', 'Support', 'PDF & CSV Attachments',
            ]);

        Spark::plan('White Label', 'white_label')
            ->price(150.00)
            ->features([
                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '1500 Reports/month', 'Support', 'PDF & CSV Attachments', 'Your Own Logo on Reports',
            ]);
    }
}
