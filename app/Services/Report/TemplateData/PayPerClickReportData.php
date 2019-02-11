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
            'revenue' => null,
            'spend' => null,
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
        $topCountriesReportData = $adwordsReporting->getTopCountriesReport($fromDate,$toDate);
        $adCliksByDay = $adwordsReporting->getCampaignClicksByDayReport($fromDate,$toDate);
        // dd($cliksByDay);
        $adImpressions =  $campaignReportData['total']['Impressions'];
        $adClicks = $campaignReportData['total']['Clicks'];
        $adSpend = $campaignReportData['total']['Cost'];
        $adCtr =  $campaignReportData['total']['CTR'];
        $adAvgCpc =  $campaignReportData['total']['Avg. CPC'];
        $adConversions = $campaignReportData['total']['All conv.'];

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
        $fbClicksByDay = $facebookAdsReporting->clicksByDay();
        // dd($fbClicksByDay);
        $topPerformingCampaigns = $facebookAdsReporting->topPerformingCampaigns();
        foreach ($topPerformingCampaigns['data'] as $entry) {
            # code...
        }
        $topPerformingCampaigns['data'] = array_map(function ($entry) {
                $entry['conversions'] = null;
                $entry['revenue'] = null;
                return $entry;
            },$topPerformingCampaigns['data']);
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

        $spendConversion = array( 
            array (
               "name" => "Conversions",
               "fb_ads" => 000000,	
               "google_ads" => $adConversions
            ),
            array (
               "name" => "Clicks",
               "fb_ads" => $fbClicks,
               "google_ads" => $adClicks
            ),
            array (
               "name" => "Impressions",
               "fb_ads" => $fbImpressions,
               "google_ads" => $adImpressions
            ),
            array (
                "name" => "Spend($)",
                "fb_ads" => $fbSpend,
                "google_ads" => $adSpend 
             )
         );
        //  dd($spendConversion);
        $this->data = [
            'impressions' => $adImpressions + $fbImpressions,
            'clicks' => $adClicks + $fbClicks,
            'revenue' => null,
            'spend' =>  $adSpend + $fbSpend,
            'ctr' => (str_replace('%', '', $adCtr) + $fbCtr )/ 2,
            'avg_cpc' => $adAvgCpc + $fbAvgCpc,
            'ad_age_genders_devices' => $demographicsReportData,
            'fb_age_genders_devices' => $age_genders_devices,
            'ad_performance_by_country'=> $topCountriesReportData,
            'fb_performance_by_country'=> $topPerformingCountries['data'],
            'spend_conversion_by_day'=> $spendConversion,

            'ad_top_performing_campaigns' => $campaignReportData['rows'],
            'fb_top_performing_campaigns' => $topPerformingCampaigns['data'],

            'ad_clicks_by_day' =>$adCliksByDay,
            'fb_clicks_by_day' =>$fbClicksByDay['data']
            
        
        ];
        //  dd($this->data);
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
        // TOP CLICKS BY COUNTRY adwords
        if ($this->data['ad_performance_by_country']) {
            $mapData =  array_reduce($this->data['ad_performance_by_country'],function ($result,$item) {
                $result[$item['CountryISO']] = (int) $item['Clicks'];
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData,[
                'color_shades' => (new \App\Services\ColorShades)->green()
                ]);
            $emailData['ad_performance_by_country_chart_url'] = $url;
        }
        // TOP CLICKS BY COUNTRY FACEBOOK
        if ($this->data['fb_performance_by_country']) {
            $mapData =  array_reduce($this->data['fb_performance_by_country'],function ($result,$item) {
                $result[$item['country_code']] = (int) $item['clicks'];
                return $result;
            },[]);    
            $url = $this->chartService->getMapChartImageUrl($mapData,[
                'color_shades' => (new \App\Services\ColorShades)->chiliPepper()
                ]);
            $emailData['fb_performance_by_country_chart_url'] = $url;
        }
        // SPEND/CONVERSION BY DAY
        if($this->data['spend_conversion_by_day']){
            $conversion_by_day = array_get($this->data['spend_conversion_by_day'],'0');
            $clicks_by_day = array_get($this->data['spend_conversion_by_day'],'1');
            $impressions_by_day = array_get($this->data['spend_conversion_by_day'],'2');
            $spend_by_day = array_get($this->data['spend_conversion_by_day'],'3');
            
            $emailData['conversion_by_day'] = $this->chartService->getDonutChartImageUrl([['Facebook ads',$conversion_by_day['fb_ads']],['Goole ads',$conversion_by_day['google_ads']]]);
            $emailData['clicks_by_day'] = $this->chartService->getDonutChartImageUrl([['Facebook ads',$clicks_by_day['fb_ads']],['Goole ads',$clicks_by_day['google_ads']]]);
            $emailData['impressions_by_day'] = $this->chartService->getDonutChartImageUrl([['Facebook ads',$impressions_by_day['fb_ads']],['Goole ads',$impressions_by_day['google_ads']]]);
            $emailData['spend_by_day'] = $this->chartService->getDonutChartImageUrl([['Facebook ads',$spend_by_day['fb_ads']],['Goole ads',$spend_by_day['google_ads']]]);
        }
        // AGES, GENDERS, DEVICES adwords
        if ($this->data['ad_age_genders_devices']) {
            $ageChartData = $this->chartService->generateBarChartData(
                $this->data['ad_age_genders_devices']['age'],
                ['label-key' => 'AgeRange','data-key' => 'Impressions']
            );
            $ageChartUrl= $this->chartService->getBarChartImageUrl(
                $ageChartData['labels'],
                $ageChartData['data'],
                [
                    'bar-color' => 'rgb(0, 191, 255)',
                    'title' => 'Users by Age'
                ]
            );

            $genderChartData = $this->chartService->generateDonutChartData(
                $this->data['ad_age_genders_devices']['genders'],
                ['label-key' => 'Gender','data-key' => 'Impressions']
            );
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);


            $deviceChartData = $this->chartService->generateBarChartData(
                $this->data['ad_age_genders_devices']['devices'],
                ['label-key' => 'Device','data-key' => 'Impressions']
            );

            $deviceChartUrl= $this->chartService->getBarChartImageUrl(
                $deviceChartData['labels'],
                $deviceChartData['data'],
                [
                    'bar-color' => 'rgb(60, 179, 113)',
                    'title' => 'Users by Devices'
                ]
            );

            $emailData['ad_age_genders_devices_chart_url'] = [
                'age' => $ageChartUrl,
                'genders' => $genderChartUrl,
                'devices' => $deviceChartUrl
            ];
           
        }
        //facebook
        if ($this->data['fb_age_genders_devices']) {
            $ageChartData = $this->chartService->generateBarChartData(
                $this->data['fb_age_genders_devices']['age'],
                ['label-key' => 'age','data-key' => 'impressions']
            );
            $ageChartUrl= $this->chartService->getBarChartImageUrl(
                $ageChartData['labels'],
                $ageChartData['data'],
                [
                    'bar-color' => 'rgb(0, 191, 255)',
                    'title' => 'Impressions by age'
                ]
            );

            $genderChartData = $this->chartService->generateDonutChartData(
                $this->data['fb_age_genders_devices']['genders'],
                ['label-key' => 'gender','data-key' => 'impressions']
            );
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);


            $deviceChartData = $this->chartService->generateBarChartData(
                $this->data['fb_age_genders_devices']['devices'],
                ['label-key' => 'impression_device','data-key' => 'impressions']
            );

            $deviceChartUrl= $this->chartService->getBarChartImageUrl(
                $deviceChartData['labels'],
                $deviceChartData['data'],
                [
                    'bar-color' => 'rgb(60, 179, 113)',
                    'title' => 'Impressions by devices'
                ]
            );

            $emailData['fb_age_genders_devices_chart_url'] = [
                'age' => $ageChartUrl,
                'genders' => $genderChartUrl,
                'devices' => $deviceChartUrl
            ];
           
        }
        // graph
        if ($this->data['ad_clicks_by_day'] && $this->data['fb_clicks_by_day']) 
        {
            $this->data['fb_clicks_by_day'] = array_map(function ($entry) {
                $entry['Day'] = $entry['date_start'];
                return $entry;
            },$this->data['fb_clicks_by_day']);
            // test ad
            // $this->data['ad_clicks_by_day']['rows'] = array_map(function ($entry) {
            //     $entry['Clicks'] = rand(10,100);
            //     return $entry;
            // },$this->data['ad_clicks_by_day']['rows']);
            // 
            $chartData = $this->chartService->generateComboChartData(
                array_merge($this->data['ad_clicks_by_day']['rows'],$this->data['fb_clicks_by_day']),
                'Day',
                ['Clicks','clicks']
            );
            // dd( $chartData);
            $url = $this->chartService->getComboChartImageUrl(
                $chartData['labels'],
                [
                    [
                        'label' => 'Facebook',
                        'type' => 'line',
                        'data' => $chartData['dataset']['clicks'],
                        'borderColor' => '#1976d2',
                        'backgroundColor' => 'transparent'
                    ],
                    [
                        'label' => 'Adwords',
                        'type' => 'line',
                        'data' => $chartData['dataset']['Clicks'],
                        'borderColor' => '#b71c1c',
                        'backgroundColor' => 'transparent'

                    ],
        
                ],
                [
                    'title' => 'Spend vs Clicks By Day'
                ]
            );
            $emailData['daily_clicks_by_ppc'] = $url;
        }
        return $emailData;
    }
}


