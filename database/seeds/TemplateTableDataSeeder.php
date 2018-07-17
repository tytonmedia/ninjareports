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
            DB::table('email_templates')->insert([
                'template_id' => 'a62644eb-9c36-40bf-90f5-09addbbef798',
                'name' => 'Audience Overview',
                'account_type' => 'analytics',
                'report_items'=>'total_report,graph_click_device,graph_top_location,list_top_visiter',
                'status' => 'active',
            ]);
			
            DB::table('email_templates')->insert([
                'template_id' => 'c4bd0d24-adf2-42c8-be9d-76afc49e4a20',
                'name' => 'Behavior Overview',
                'account_type' => 'analytics',
                'report_items'=>'total_report,graph_pageview,list_top_pageview',            
                'status' => 'active',
            ]);
            
            DB::table('email_templates')->insert([
                'template_id' => '0f8fb97f-164c-4260-97f7-6674e1c6dc59',
                'name' => 'Acquisition Overview',
                'account_type' => 'analytics',
                'report_items'=>'total_report,list_top_visiter,graph_top_chanels,graph_top_location,list_top_campaigns',            
                'status' => 'active',
            ]);
            
            DB::table('email_templates')->insert([
                'template_id' => '469d4ce7-6c32-4c51-a9dd-c11d2a416eaa',
                'name' => 'Stripe Overview',
                'account_type' => 'stripe',
                'report_items'=>'',            
                'status' => 'active',
            ]);
            DB::table('email_templates')->insert([
                'template_id' => '0a98196e-646c-45ff-af50-5826009e72ab',
                'name' => 'Adwords Overview',
                'account_type' => 'adwords',
                'report_items'=>'',            
                'status' => 'active',
            ]);
            DB::table('email_templates')->insert([
                'template_id' => '56c13cc8-0a27-40e0-bd31-86ffdced98ae',
                'name' => 'Facebook Ads Overview',
                'account_type' => 'facebook',
                'report_items'=>'',            
                'status' => 'active',
            ]);
            DB::table('email_templates')->insert([
                'template_id' => 'cfd85514-181f-4abc-9032-5fe464704c4b',
                'name' => 'Search Console Overview',
                'account_type' => 'google-search',
                'report_items'=>'',            
                'status' => 'active',
            ]);
            
            
            
    }
}
