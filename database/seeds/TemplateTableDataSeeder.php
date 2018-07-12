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
                'report_items'=>'total_report,graph_click_device,graph_top_location,list_top_visiter',
                'status' => 'active',
            ]);
			
            DB::table('template')->insert([
                'template_id' => '05815a19-59be-45be-b111-72a614698248',
                'name' => 'Google Analytics Example',
                'account_type' => 'analytics',
                'report_items'=>'total_report,graph_click_device,graph_top_location,list_top_visiter',            
                'status' => 'active',
            ]);
            
            DB::table('template')->insert([
                'template_id' => 'c4bd0d24-adf2-42c8-be9d-76afc49e4a20',
                'name' => 'Behavior_overview',
                'account_type' => 'analytics',
                'report_items'=>'total_report,graph_pageview,list_top_pageview',            
                'status' => 'active',
            ]);
            
            DB::table('template')->insert([
                'template_id' => '0f8fb97f-164c-4260-97f7-6674e1c6dc59',
                'name' => 'Acquisition_overview',
                'account_type' => 'analytics',
                'report_items'=>'total_report,list_top_visiter,graph_top_chanels,graph_top_location,list_top_campaigns',            
                'status' => 'active',
            ]);
    }
}
