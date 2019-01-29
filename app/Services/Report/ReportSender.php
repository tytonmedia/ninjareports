<?php

namespace App\Services\Report;

use App\Models\ReportTemplate;
use App\Services\PDFGenerator;
 
class ReportSender
{
    public function send($report)
    {
        $reportTemplate = $report->template;
        $reportAccounts = $report->accounts;
        $reportRecipients = explode(',',$report->recipients);

        $dates = $this->extractReportDates($report);
        $parsedReportAccounts = $this->parseReportAccounts($reportAccounts);
        $html = null;
        $attachments = [];
        $templateId = null;
        $from = 'reports@ninjareports.com';

        switch ($reportTemplate->slug) {
            case 'seo-report':
                $templateId = 'd-55d9ef2ca3bc4fc181e12d96fc109ef6';
                $reportEmailData = app('App\Services\Report\TemplateData\SEOReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                dd($reportEmailData);
                // pdf generation
                $reportEmailData['report_date'] = $reportEmailData['report_date'];
                if ($report->attachment_type == 'pdf') {
                    // $html = view('reports.templates.seo-report',['data' => $reportEmailData])->render();
                } 
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

        if ($html) {
            $pdfGenerator = new PDFGenerator();
            $filePath = public_path('files/pdf/'.time().'.pdf');
            $pdfGenerator->generate($html,$filePath);
            $attachments = [
                [ 'file' => $filePath, 'name' => $report->title.'.pdf'],
            ];
        }

        foreach ($reportRecipients as $recipient) {
            (new \App\Services\SendGridService)->sendTransactionalMail([
                'to' => ['email' => $recipient],
                'template_id' => $templateId,
                'template_data' => $reportEmailData,
                'subject' => $report->email_subject,
                'attachments' => $attachments
            ]);
        }

        if ($attachments) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // dd($reportEmailData);
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
            $accessToken = json_decode($analyticsAccount->ad_account->account->token,true);
            $parsedAccounts['google_analytics'] = [
                'profile_id' => $analyticsAccount->profile->view_id,
                'access_token' => $accessToken
            ];
        }

        if ($googleSearchAccount = array_get($reportAccountsByType,'google-search')) {
            $accessToken = json_decode($googleSearchAccount->ad_account->account->token,true);
            $parsedAccounts['google_search_console'] = [
                'site_url' =>$googleSearchAccount->ad_account->ad_account_id,
                'access_token' => $accessToken
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