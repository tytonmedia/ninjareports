<?php

namespace App\Services\Report\TemplateData;

use App\Services\ChartService;

 
class FacebookAdsReportData
{
    private $requiredAccountTypes = ['google_analytics','facebook'];
    private $accounts = null;
    private $data = null;

    public function __construct(ChartService $chartService) { 
         $this->chartService = $chartService; 
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
                'account_id =>''
            ]
        ]
     */
    public function setAccounts($accounts)
    {
        if (!array_has($accounts,$this->requiredAccountTypes)) {
            throw $this->invalidAccountsException();
        }
        $this->accounts = array_only($accounts,$this->requiredAccountTypes);
        return $this;
    }

    public function generate($fromDate,$toDate)
    {
        if (!array_has($this->accounts,$this->requiredAccountTypes)) {
            throw $this->invalidAccountsException();
        }

        $facebookAccessToken = $this->accounts['facebook']['access_token'];
        $facebookAccountId = $this->accounts['facebook']['account_id'];


        $facebookAdsReporting = app('FacebookAdsReporting')
                ->init($facebookAccessToken,$facebookAccountId)
                ->betweenDates($fromDate,$toDate);

        $accountOverview = $facebookAdsReporting->accountOverview();
        $spendByDay = $facebookAdsReporting->spendByDay();
        $clicksByDay = $facebookAdsReporting->clicksByDay();
        $demographics = $facebookAdsReporting->demographics();
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

        $this->data = [
            'spend' => array_get($accountOverview,'data.0.spend'),
            'impressions' => array_get($accountOverview,'data.0.impressions'),
            'ctr' => array_get($accountOverview,'data.0.ctr'),
            'clicks' => array_get($accountOverview,'data.0.clicks'),
            'avg_cpc' => array_get($accountOverview,'data.0.cpc'),
            'frequency' => array_get($accountOverview,'data.0.frequency'),
            'spend_by_day' => $spendByDay['data'],
            'clicks_by_day' => $clicksByDay['data'],
            'age_genders_devices' => [
                'age' => $demographics['age']['data'],
                'genders' => $demographics['genders']['data'],
                'devices' => $demographics['devices']['data']
            ],
            'top_performing_campaigns' => $topPerformingCampaigns['data'],
            'performance_by_country'=> $topPerformingCountries['data']
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

        if ($this->data['spend']) {
            $emailData['spend'] = number_format($this->data['spend'], 2, '.', '');
        }

        if ($this->data['impressions']) {
            $emailData['impressions'] = number_format($this->data['impressions']);
        }

        if ($this->data['clicks']) {
            $emailData['clicks'] = number_format($this->data['clicks']);
        }

        if ($this->data['ctr']) {
            $emailData['ctr'] = number_format($this->data['ctr'], 2, '.', '');
        }

        if ($this->data['avg_cpc']) {
            $emailData['avg_cpc'] = number_format($this->data['avg_cpc'], 2, '.', '');
        }

        if ($this->data['frequency']) {
            $emailData['frequency'] = number_format($this->data['frequency'], 2, '.', '');
        }

        if ($this->data['age_genders_devices']) {
            $ageChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['age'],
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
                $this->data['age_genders_devices']['genders'],
                ['label-key' => 'gender','data-key' => 'impressions']
            );
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);


            $deviceChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['devices'],
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

            $emailData['age_genders_devices_chart_url'] = [
                'age' => $ageChartUrl,
                'genders' => $genderChartUrl,
                'devices' => $deviceChartUrl
            ];
           
        }

        if ($this->data['performance_by_country']) {
            $mapData =  array_reduce($this->data['performance_by_country'],function ($result,$item) {
                $result[$item['country_code']] = (int) $item['clicks'];
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['performance_by_country_chart_url'] = $url;
        }

        if ($this->data['spend_by_day'] && $this->data['clicks_by_day']) {
            $chartData = $this->chartService->generateComboChartData(
                array_merge($this->data['spend_by_day'],$this->data['clicks_by_day']),
                'date_start',
                ['spend','clicks']
            );
            $url = $this->chartService->getComboChartImageUrl(
                $chartData['labels'],
                [
                    [
                        'label' => 'Clicks',
                        'type' => 'line',
                        'data' => $chartData['dataset']['clicks'],
                        'borderColor' => '#1976d2',
                        'backgroundColor' => 'transparent'
                    ],
                    [
                        'label' => 'Spend',
                        'type' => 'bar',
                        'data' => $chartData['dataset']['spend'],
                        'backgroundColor' => '#b71c1c'
                    ],
        
                ],
                [
                    'title' => 'Spend vs Clicks By Day'
                ]
            );
            $emailData['spend_and_clicks_by_day_chart_url'] = $url;
        }

        return $emailData;
    }

    public function invalidAccountsException()
    {
        return new InvalidArgumentException(implode(', ',$this->requiredAccountTypes)
            .' accounts are required for generating report');
    }
}


