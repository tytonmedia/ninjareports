<?php

namespace App\Services\Report;

use App\Models\ReportTemplate;
use App\Services\PDFGenerator;
use App\Services\Report\ReportSenderException;
use App\Services\Report\ReportSenderResult;
 
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

        $reportSenderResult = new ReportSenderResult();

        switch ($reportTemplate->slug) {
            case 'seo-report':
                $templateId = 'd-55d9ef2ca3bc4fc181e12d96fc109ef6';
                $reportEmailData = app('App\Services\Report\TemplateData\SEOReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                // pdf generation
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.seo-report-new',['data' => $reportEmailData])->render();
                } 
                break;
            case 'google-ads-report':
                $templateId = 'd-402d11efa1f345c9acf57605c71834f3';
                $reportEmailData = app('App\Services\Report\TemplateData\GoogleAdsReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.google-ads-report',['data' => $reportEmailData])->render();
                }

                break;
            
            case 'traffic-report':
                $templateId = 'd-9a2700aa5c404629abf569391c9a92f8';
                $reportEmailData = app('App\Services\Report\TemplateData\TrafficReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.traffic-report',['data' => $reportEmailData])->render();
                }
                break;

            case 'facebook-ads-report':
                $templateId = 'd-3886dea75e10438287ac9c709a88eb81';
                $reportEmailData = app('App\Services\Report\TemplateData\FacebookAdsReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.facebook-ads-report',['data' => $reportEmailData])->render();
                }
                break;
            case 'ecommerce-report':
                $templateId = 'd-6375b7ff468a4eda909d50e02b4d0b7e';
                $reportEmailData = app('App\Services\Report\TemplateData\EcommerceReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.ecommerce-report',['data' => $reportEmailData])->render();
                }
                break;
            case 'pay-per-click-report':
                $templateId = 'd-814771772eba4b7496ade3cde6229e89';
                $reportEmailData = app('App\Services\Report\TemplateData\PayPerClickReportData')
                                ->setAccounts($parsedReportAccounts)
                                ->generate($dates['from_date'],$dates['to_date'])
                                ->get('email');
                
                $reportEmailData['report_date'] = $dates['report_date'];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.pay-per-clicks-report',['data' => $reportEmailData])->render();
                }
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

        $receivers = [];
        $sentCount = 0;
        foreach ($reportRecipients as $recipient) {
            (new \App\Services\SendGridService)->sendTransactionalMail([
                'to' => ['email' => $recipient],
                'template_id' => $templateId,
                'template_data' => $reportEmailData,
                'subject' => $report->email_subject,
                'attachments' => $attachments
            ]);
            $receivers[] = (object)['email' => $recipient];
            $sentCount++;
        }

        $reportSenderResult->setReceivers($receivers);
        $reportSenderResult->totalSentCount = $sentCount;
        $reportSenderResult->data = $reportEmailData;

        if ($attachments) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $reportSenderResult;
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
                $reportDate =  date('m/d/Y',strtotime($fromDate)). " - " .date('m/d/Y',strtotime($toDate));
                break;
            case "monthly":
                $fromDate = date('Y-m-d', strtotime('-1 month', strtotime($report->next_send_time)));
                $reportDate = date('m/d/Y',strtotime($fromDate)). " - " .date('m/d/Y',strtotime($toDate));
                break;
            case "yearly":
                $fromDate = date('Y-m-d', strtotime('-1 year', strtotime($report->next_send_time)));
                $reportDate = date("Y",strtotime($fromDate));
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
            $accessToken = json_decode($googleAdwordsAccount->ad_account->account->token,true);
            if (!$accessToken) {
                throw new ReportSenderException('Report Account Parsing : Invalid adwords token');
            }
            $parsedAccounts['google_adwords'] = [
                'client_customer_id' =>$googleAdwordsAccount->ad_account->ad_account_id,
                'access_token' => $accessToken['refresh_token']
            ];
        }

        if ($facebookAdsAccount = array_get($reportAccountsByType,'facebook')) {
            $accessToken = $facebookAdsAccount->ad_account->account->token;
            $parsedAccounts['facebook'] = [
                'account_id' =>$facebookAdsAccount->ad_account->ad_account_id,
                'access_token' => $accessToken
            ];
        }
        return $parsedAccounts;
    }
}