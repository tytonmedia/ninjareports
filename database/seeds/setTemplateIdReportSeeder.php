<?php

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\Emailtemplate;

class setTemplateIdReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = Emailtemplate::pluck('id','account_type')->toArray();
        
        $reports = Report::with('account')->get();
        
        foreach($reports as $report){
            
            
            if($report->template && isset($report->template->template_id)){
               // already set
            }else{
                if($report->account){
                    if($report->account->type=="facebook" && isset($templates['facebook'])){
                         $report->template_id = $templates['facebook'];
                         $report->save();
                    }else if($report->account->type=="analytics" && isset($templates['analytics'])){
                         $template = Emailtemplate::where('template_id',"a62644eb-9c36-40bf-90f5-09addbbef798")->first();
                         if($template){
                             $report->template_id = $template->id;
                             $report->save();
                         }
                    }else if($report->account->type=="adword" && isset($templates['adword'])){
                         $report->template_id = $templates['adword'];
                         $report->save();
                    }else if($report->account->type=="stripe" && isset($templates['stripe'])){
                         $report->template_id = $templates['stripe'];
                         $report->save();
                    }else if($report->account->type=="google-search" && isset($templates['google-search'])){
                            $report->template_id = $templates['google-search'];
                         $report->save();
                    }
                }
            }
        
            
        }
    }
}
