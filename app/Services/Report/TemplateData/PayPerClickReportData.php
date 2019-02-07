<?php

namespace App\Services\Report\TemplateData;
 
use App\Services\GoogleAdwordsReporting;
use InvalidArgumentException;
use App\Services\ChartService;
use App\Services\GoogleAnalyticsService;

class PayPerClickReportData
{
    private $requiredAccountTypes = ['google_analytics','google_adwords','facebook'];
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
        // $topCountriesReportData = $adwordsReporting->getTopCountriesReport($fromDate,$toDate);
        // dd($topCountriesReportData);
        $adImpressions =  $campaignReportData['total']['Impressions'];
        $adClicks = $campaignReportData['total']['Clicks'];
        $adSpend = $campaignReportData['total']['Cost'];
        $adCtr =  $campaignReportData['total']['CTR'];
        $adAvgCpc =  $campaignReportData['total']['Avg. CPC'];

        /**
         * facebook data fetching
         */

        $facebookAccessToken = $this->accounts['facebook']['access_token'];
        $facebookAccountId = $this->accounts['facebook']['account_id'];
        $facebookAdsReporting = app('FacebookAdsReporting')
                ->init($facebookAccessToken,$facebookAccountId)
                ->betweenDates($fromDate,$toDate);
        $accountOverview = $facebookAdsReporting->accountOverview();
        $demographics = $facebookAdsReporting->demographics();
        $topPerformingCountries = $facebookAdsReporting->topPerformingCountries();
        // dd($topPerformingCountries);
        $age_genders_devices = [
            'age' => $demographics['age']['data'],
            'genders' => $demographics['genders']['data'],
            'devices' => $demographics['devices']['data']
        ];
        // dd($age_genders_devices);
        $fbImpressions = array_get($accountOverview,'data.0.impressions');
        $fbClicks = array_get($accountOverview,'data.0.clicks');
        $fbSpend = array_get($accountOverview,'data.0.spend');
        $fbCtr = array_get($accountOverview,'data.0.ctr');
        $fbAvgCpc =  array_get($accountOverview,'data.0.cpc');

        $this->data = [
            'impressions' => $adImpressions + $fbImpressions,
            'clicks' => $adClicks + $fbClicks,
            // 'revenue' => null,
            'spend' =>  $adSpend + $fbSpend,
            'ctr' => (str_replace('%', '', $adCtr) + $fbCtr )/ 100,
            'avg_cpc' => $adAvgCpc + $fbAvgCpc,
            // 'ad_performance_by_country'=> $topCountriesReportData,
            'fb_performance_by_country'=> $topPerformingCountries['data'],
            'spend_conversion_by_day'=>""
            
        
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
        $emailData = $this->data;
        if ($this->data['impressions']) {
            $emailData['impressions'] = number_format($this->data['impressions']);
        }
        if ($this->data['clicks']) {
            $emailData['clicks'] = number_format($this->data['clicks']);
        }
        if ($this->data['spend']) {
            $emailData['spend'] = number_format($this->data['spend']);
        }
        if ($this->data['ctr']) {
            $emailData['ctr'] = number_format($this->data['ctr'], 2, '.', '');
        }
        if ($this->data['avg_cpc']) {
            $emailData['avg_cpc'] = number_format($this->data['avg_cpc'], 2, '.', '');
        }
        // if ($this->data['ad_performance_by_country']) {
        //     $mapData =  array_reduce($this->data['ad_performance_by_country'],function ($result,$item) {
        //         $result[$item['CountryISO']] = $item['Clicks'];
        //         return $result;
        //     },[]);
        //     $url = $this->chartService->getMapChartImageUrl($mapData);
        //     $emailData['ad_performance_by_country_chart_url'] = $url;
        // }
        if ($this->data['fb_performance_by_country']) {
            $countries = new \PragmaRX\Countries\Package\Countries();
            $mapData =  array_reduce($this->data['fb_performance_by_country'],function ($result,$item) use($countries) {
                $country = $countries->where('cca2',$item['country'])->first();
                if ($country->isNotEmpty()) {
                    $result[$country->cca3] = (int) $item['clicks'];
                }
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['fb_performance_by_country_chart_url'] = $url;
        }
        return $emailData;
    }
}


