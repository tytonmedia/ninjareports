<?php

use Illuminate\Database\Seeder;

class TemplateTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('template')->insert([
                'template_id' => 'a62644eb-9c36-40bf-90f5-09addbbef798',
                'name' => 'Google Analytics: Audience Overview',
                'account_type' => 'analytics',
                'status' => 'active',
            ]);
			
			DB::table('template')->insert([
                'template_id' => '05815a19-59be-45be-b111-72a614698248',
                'name' => 'Google Analytics Example',
                'account_type' => 'analytics',
                'status' => 'active',
            ]);
    }
}
