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
		'product' => 'Ninja Reports',
		'street' => '5601 west 136th tr',
		'location' => 'Overland Park, KS 66223',
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
		'tyler@ninjareports.com',
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
//        Spark::plan('Free Trial', 'free_trial')
//            ->archived()
//            ->price(0.00)
//            ->features([
//                'UNLIMITED Integrations', 'UNLIMITED Recipients per Report', '5 Reports/month',
//            ]);
		//Spark::useStripe()->noCardUpFront()->trialDays(7);

		Spark::plan('Personal', 'personal')
			->price(10.00)
			->archived()
			->trialDays(7)
			->features([
				'UNLIMITED Integrations',
				'UNLIMITED Recipients per Report',
				'50 Reports/month',
				'Support',
				'PDF Attachments',
			]);

		Spark::plan('Personal', 'plan_EtggfzAXYCmych')
			->price(15.00)
			->trialDays(7)
			->features([
				'UNLIMITED Integrations',
				'UNLIMITED Recipients per Report',
				'50 Reports/month',
				'Support',
				'PDF Attachments',
			]);

		Spark::plan('Business', 'plan_DGFvYJYXMmSLXU')
			->price(25.00)
			->trialDays(7)
			->features([
				'UNLIMITED Integrations',
				'UNLIMITED Recipients per Report',
				'300 Reports/month',
				'Support',
				'PDF Attachments',
			]);

		Spark::plan('Premium', 'plan_DGFyyak7WZ7qJN')
			->price(55.00)
			->trialDays(7)
			->features([
				'UNLIMITED Integrations',
				'UNLIMITED Recipients per Report',
				'800 Reports/month',
				'Support',
				'PDF Attachments',
			]);

		Spark::plan('White Label', 'plan_DGFzHetRDJU1jz')
			->price(75.00)
			->trialDays(7)
			->features([
				'UNLIMITED Integrations',
				'UNLIMITED Recipients per Report',
				'1500 Reports/month',
				'Support',
				'PDF Attachments',
				'Logo on Reports',
			]);
	}
}
