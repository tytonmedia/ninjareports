<?php

namespace App\Services\Report\TemplateData;
 
use App\Services\GoogleAdwordsReporting;
use InvalidArgumentException;
use App\Services\ChartService;
use App\Services\GoogleAnalyticsService;

class PayPerClickReportData
{
    private $requiredAccountTypes = ['google_analytics','google_adwords'];
    private $accounts = null;
    private $data = null;

    public function __construct(
        GoogleAdwordsReporting $googleAdwordsReporting,
        ChartService $chartService,
        GoogleAnalyticsService $googleAnalyticsService
    ) {
        $this->googleAdwordsReporting = $googleAdwordsReporting;
        $this->chartService = $chartService;
        $this->googleAnalyticsService = $googleAnalyticsService;
    }

    /*
        accounts argumnent structure
        [
            'google_analytics' => [
                'profile_id' => '',
                'access_token' => ''
            ],
            'facebook' => [
                'access_token' => '',
            ]
        ]
     */
    public function setAccounts($accounts)
    {
        if (!array_has($accounts,$this->requiredAccountTypes)) {
            return;
        }
        $this->accounts = array_only($accounts,$this->requiredAccountTypes);
        return $this;
    }

    public function generate($fromDate,$toDate)
    {
        if (!array_has($this->accounts,$this->requiredAccountTypes)) {
            return;
        }
        $this->data = [
            'impressions' => null,
            'clicks' => null,
            // 'revenue' => null,
            // 'spend' => null,
            'ctr' => null,
            'avg_cpc' => null,
        ];
        /**
         * Google analytics data fetching
         */
        $analyticsAccessToken = $this->accounts['google_analytics']['access_token'];
        $profileId = $this->accounts['google_analytics']['profile_id'];
        $analyticsReporting = $this->googleAnalyticsService->initAnalyticsReporting($analyticsAccessToken);

        /**
         * Google adwords data fetching
         */
        $adwordsAccessToken = $this->accounts['google_adwords']['access_token'];
        $adwordsCustomerId = $this->accounts['google_adwords']['client_customer_id'];
        $adwordsReporting = $this->googleAdwordsReporting->initSession($adwordsAccessToken,$adwordsCustomerId);

        $campaignReportData = $adwordsReporting->getCampaignReport($fromDate,$toDate);
        $demographicsReportData = $adwordsReporting->getAgeGenderDeviceReport($fromDate,$toDate);
        dd($demographicsReportData);
        $impressions =  $campaignReportData['total']['Impressions'];
        $clicks = $campaignReportData['total']['Clicks'];
        $ctr =  $campaignReportData['total']['CTR'];
        $avg_cpc =  $campaignReportData['total']['Avg. CPC'];
        $this->data = [
            'impressions' => $impressions,
            'clicks' => $clicks,
            // 'revenue' => null,
            // 'spend' => null,
            // 'age_genders_devices' => $demographicsReportData,
            'ctr' => $ctr,
            'avg_cpc' => $avg_cpc,
            
        
        ];
        return $this;
    }

    public function get($type = 'raw')
    {
        if ($type == 'raw') {
            return $this->data;
        } else if ($type == 'email') {
            return $this->getEmailData();
        }
    }

    public function getEmailData()
    {
        if (!$this->data) {
            return;
        }
        if ($this->data['impressions']) {
            $emailData['impressions'] = number_format($this->data['impressions']);
        }

        if ($this->data['clicks']) {
            $emailData['clicks'] = number_format($this->data['clicks']);
        }

        if ($this->data['avg_cpc']) {
            $emailData['avg_cpc'] = number_format($this->data['avg_cpc'], 2, '.', '');
        }
        return $emailData;
    }
}


