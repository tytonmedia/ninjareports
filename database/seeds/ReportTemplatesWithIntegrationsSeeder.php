<?php

use Illuminate\Database\Seeder;

class ReportTemplatesWithIntegrationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $analytics = \App\Models\Integration::where('slug','analytics')->first();
        $adwords = \App\Models\Integration::where('slug','adword')->first();
        $searchConsole = \App\Models\Integration::where('slug','google-search')->first();
        $facebook = \App\Models\Integration::where('slug','facebook')->first();

        $seoReport = \App\Models\ReportTemplate::create([
            'name' => 'SEO Report',
            'slug' => 'seo-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $seoReport->integrations()->attach([$analytics->id,$searchConsole->id]);

        
        $googleAdsReport = \App\Models\ReportTemplate::create([
            'name' => 'Google Ads Report',
            'slug' => 'google-ads-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $googleAdsReport->integrations()->attach([$analytics->id,$adwords->id]);

        $facebookAdsReport = \App\Models\ReportTemplate::create([
            'name' => 'Facebook Ads Report',
            'slug' => 'facebook-ads-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $facebookAdsReport->integrations()->attach([$analytics->id,$facebook->id]);

        $trafficReport = \App\Models\ReportTemplate::create([
            'name' => 'Traffic Report',
            'slug' => 'traffic-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $trafficReport->integrations()->attach([$analytics->id,$searchConsole->id]);

        $ecommerceReport = \App\Models\ReportTemplate::create([
            'name' => 'Ecommerce Report',
            'slug' => 'ecommerce-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $ecommerceReport->integrations()->attach([$analytics->id,$adwords->id,$facebook->id]);

        $goalAndEventReport = \App\Models\ReportTemplate::create([
            'name' => 'Goal & Event Report',
            'slug' => 'goal-and-event-report',
            'logo_path' => 'img/repor_template.png'
        ]);

        $goalAndEventReport->integrations()->attach([$analytics->id]);


    }
}
