<?php

namespace App\Services;

use App\Models\ReportTemplate;
use App\Models\Account;
use App\Models\AdAccount;
use App\Models\NinjaReport;
use App\Models\NinjaReportAccount;
use App\Models\AnalyticProperty;
use App\Models\AnalyticView;
use Auth;

 
class ReportService
{
    public function __construct() {
        
    }

    public function getActiveReport($id){
        return NinjaReport::with('template','accounts.ad_account','accounts.property','accounts.profile')->where('id', $id)->where('is_active', 1)->where('user_id', auth()->user()->id)->first();
    }
    public function getReportUser($id){
        return NinjaReport::where('id', $id)->where('is_active', 1)->where('user_id', auth()->user()->id)->first();
    }
    public function getReportObject(){
        return new NinjaReport();
    }
    public function getAdAccount($ad_account_id){
        return AdAccount::where('ad_account_id', $ad_account_id)->where('user_id',Auth::id())->first();
    }
    
    public function getTemplates()
    {
        $templates =  ReportTemplate::with('integrations')->get();
        return $templates;
    }
    public function getTemplate($slug){
        return ReportTemplate::whereSlug($slug)->with('integrations')->first();
    }
    public function getAnalyticSource($slug)
    {
        $returnData = collect();
        $reportTemplate = $this->getTemplate($slug);
        $returnData->template = $reportTemplate;
        $returnData->accounts = [];
        if($reportTemplate && $reportTemplate->integrations){
            foreach($reportTemplate->integrations as $integration){           
                $accounts = Account::with(['ad_accounts' => function ($query) {
                    $query->where('is_active',1);
                }])->whereType($integration->slug)
                    ->where('user_id',Auth::id())
                    ->first();
                $returnData->accounts[] = $accounts;
            }
        }
        
        // die;
        return $returnData;
    }
    public function saveNinjaReport($request, $user_id, $id = NULL){
        if($id){
            $report = NinjaReport::find($id);
        } else {
            $report = new NinjaReport();
        }
        $report->title = $request->title;
        $report->recipients = $request->recipients;
        $report->email_subject = $request->email_subject;
        $report->attachment_type = $request->attachment_type;
        $report->frequency = $request->frequency;
        $report->ends_on = $request->ends_at;
        $report->ends_at = $request->frequency_time;
        $report->data_from = $request->data_from;
        $report->next_send_time = $request->next_send_time;
        $report->template_id = $request->template_id;
        $report->user_id = $user_id;
        if($report->save()){
            $this->saveReportAccounts($request, $report->id);
            return true;
        } else {
            return false;
        }
    }
    public function saveReportAccounts($request, $reportId){

        NinjaReportAccount::where('report_id', $reportId)->delete();
        $reportAccounts = [];
        foreach ($request->sources as $accountId => $account) {
            if(isset($account['ad_account_id'])){
                $adAccount = $this->getAdAccount($account['ad_account_id']);
                if(!$adAccount){
                    $adAccount = AdAccount::where('id', $account['ad_account_id'])->where('user_id',Auth::id())->first();
                }
                if($adAccount){
                    $ninjaReportAccount = new NinjaReportAccount();
                    $ninjaReportAccount->report_id = $reportId;
                    $ninjaReportAccount->account_id = $accountId;
                    $ninjaReportAccount->ad_account_id = $adAccount->id;
                    $ninjaReportAccount->property_id = isset($account['property']) ? $this->getPropertyId($account['property']): 0;
                    $ninjaReportAccount->profile_id = isset($account['profile']) ? $this->getProfileId($account['profile']): 0;
                    $ninjaReportAccount->save();
                }
                
            }
        }
        return true;
        
    }
    public function getPropertyId($property){
        return AnalyticProperty::where('property', $property)
        ->where('user_id', auth()->user()->id)
        ->pluck('id')
        ->first();
    }
    public function getProfileId($profile){
        return AnalyticView::where('view_id', $profile)
        ->where('user_id', auth()->user()->id)
        ->pluck('id')
        ->first();
    }
}

