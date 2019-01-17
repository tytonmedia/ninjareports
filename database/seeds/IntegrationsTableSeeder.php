<?php

use Illuminate\Database\Seeder;

class IntegrationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('integrations')
            ->insert([
                [
                    'name' => 'Google Analytics',
                    'slug' => 'analytics',
                    'logo_path' => 'img/analytics.png'
                ],
                [
                    'name' => 'Google Adwords',
                    'slug' => 'adword',
                    'logo_path' => 'img/google-adwords.png'
                ],
                [
                    'name' => 'Stripe Connect',
                    'slug' => 'stripe',
                    'logo_path' => 'img/stripe.png'
                ],
                [
                    'name' => 'Google Search Console',
                    'slug' => 'google-search',
                    'logo_path' => 'img/google-search.png'
                ],
                [
                    'name' => 'Facebook Ads',
                    'slug' => 'facebook',
                    'logo_path' => 'img/facebook.png'
                ]
            ]);
    }
}
