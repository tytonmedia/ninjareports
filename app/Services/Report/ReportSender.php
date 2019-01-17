<?php

namespace App\Services\Report;

use App\Models\ReportTemplate;
use function GuzzleHttp\json_decode;
 
class ReportSender
{
    public function send($report)
    {
        $reportTemplate = $report->template;
        $reportAccounts = $report->accounts;
        $reportRecipients = explode(',',$report->recipients);

        $dates = $this->extractReportDates($report);
        $parsedReportAccounts = $this->parseReportAccounts($reportAccounts);

        switch ($reportTemplate->slug) {
            case 'seo-report':
                $reportEmailData = app('App\Services\Report\TemplateData\SeoReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                // pdf generation
                break;
            case 'google-ads-report':
                // $reportEmailData = app('App\Services\Report\TemplateData\GoogleAdsReportData')
                //                 ->setAccounts($parsedReportAccounts)
                //                 ->generate($dates['from_date'],$dates['to_date'])
                //                 ->get();
                break;
            
            case 'traffic-report':
                $reportEmailData = app('App\Services\Report\TemplateData\TrafficReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                # code...
                break;
            default:
                # code...
                break;
        }
        dd($reportEmailData);
    }

    public function extractReportDates($report)
    {
        $toDate = date('Y-m-d', strtotime($report->next_send_time));
        $report->user->timezone ? date_default_timezone_set($report->user->timezone) : '';
        $reportDate = date("m/d/Y");
        date_default_timezone_set('Europe/London');
        switch ($report->frequency) {
            case "weekly":
                $fromDate = date('Y-m-d', strtotime('-7 day', strtotime($report->next_send_time)));
                $reportDate = date("m/d/Y", strtotime("-7 Days")) . "-" . date("m/d/Y");
                break;
            case "monthly":
                $fromDate = date('Y-m-d', strtotime('-1 month', strtotime($report->next_send_time)));
                $reportDate = date("m/d/Y", strtotime("-30 Days")) . "-" . date("m/d/Y");
                break;
            case "yearly":
                $fromDate = date('Y-m-d', strtotime('-1 year', strtotime($report->next_send_time)));
                $reportDate = date("Y");
                break;
            default:
                $fromDate = date('Y-m-d');
                $toDate = date('Y-m-d');
        }
        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report_date' => $reportDate
        ];
    }

    public function parseReportAccounts($reportAccounts)
    {
        $parsedAccounts = [];
        $reportAccountsByType = $reportAccounts->keyBy('ad_account.account.type');

        if ($analyticsAccount = array_get($reportAccountsByType,'analytics')) {
            $tokenData = json_decode($analyticsAccount->ad_account->account->token,true);
            $parsedAccounts['google_analytics'] = [
                'profile_id' => $analyticsAccount->ad_account->ad_account_id,
                'access_token' => $tokenData['access_token']
            ];
        }

        if ($googleSearchAccount = array_get($reportAccountsByType,'google-search')) {
            $tokenData = json_decode($googleSearchAccount->ad_account->account->token,true);
            $parsedAccounts['google_search_console'] = [
                'site_url' =>$googleSearchAccount->ad_account->ad_account_id,
                'access_token' => $tokenData['access_token']
            ];
        }

        if ($googleAdwordsAccount = array_get($reportAccountsByType,'adword')) {
            $token = $googleAdwordsAccount->ad_account->account->token;
            $parsedAccounts['google_adwords'] = [
                'client_customer_id' =>$googleAdwordsAccount->ad_account->ad_account_id,
                'access_token' => $token
            ];
        }
        return $parsedAccounts;
    }
}