<?php

use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $free_trial = DB::table('plans')->where('title', 'free_trial')->first();
        if (!$free_trial) {
            DB::table('plans')->insert([
                'title' => 'free_trial',
                'reports' => 15,
                'is_csv' => 0,
                'is_pdf' => 0,
            ]);
        }
         $personal = DB::table('plans')->where('title', 'personal')->first();
        if (!$personal) {
            DB::table('plans')->insert([
                'title' => 'personal',
                'reports' => 50,
                'is_csv' => 1,
                'is_pdf' => 1,
            ]);
        }
        $business = DB::table('plans')->where('title', 'business')->first();
        if (!$business) {
            DB::table('plans')->insert([
                'title' => 'business',
                'reports' => 300,
                'is_csv' => 1,
                'is_pdf' => 1,
            ]);
        }
        $premium = DB::table('plans')->where('title', 'premium')->first();
        if (!$premium) {
            DB::table('plans')->insert([
                'title' => 'premium',
                'reports' => 800,
                'is_csv' => 1,
                'is_pdf' => 1,
            ]);
        }
        $white_label = DB::table('plans')->where('title', 'white_label')->first();
        if (!$white_label) {
            DB::table('plans')->insert([
                'title' => 'white_label',
                'reports' => 1500,
                'is_csv' => 1,
                'is_pdf' => 1,
            ]);
        }
    }
}
