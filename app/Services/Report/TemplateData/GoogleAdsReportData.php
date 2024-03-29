<?php 

namespace App\Services\Report\TemplateData;

use App\Services\GoogleAdwordsReporting;
use InvalidArgumentException;
use App\Services\ChartService;
use DatePeriod;
use DateTime;
use DateInterval;


class GoogleAdsReportData
{
    private $requiredAccountTypes = ['google_analytics','google_adwords'];
    private $accounts = null;
    private $data = null;

    public function __construct(
        GoogleAdwordsReporting $googleAdwordsReporting,
        ChartService $chartService
    ) {
        $this->googleAdwordsReporting = $googleAdwordsReporting;
        $this->chartService = $chartService;
    }

     /*
        accounts argumnent structure
        [
            'google_analytics' => [
                'profile_id' => '',
                'access_token' => ''
            ],
            'google_adwords' => [
                'access_token' => '',
                'client_customer_id' => ''
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

        /**
         * Google adwords data fetching
         */
        $adwordsAccessToken = $this->accounts['google_adwords']['access_token'];
        $adwordsCustomerId = $this->accounts['google_adwords']['client_customer_id'];
        $adwordsReporting = $this->googleAdwordsReporting->initSession($adwordsAccessToken,$adwordsCustomerId);

        $topCountriesReportData = $adwordsReporting->getTopCountriesReport($fromDate,$toDate);
        $campaignReportData = $adwordsReporting->getCampaignReport($fromDate,$toDate);
        $spendByDayReportData = $adwordsReporting->getCampaignSpendByDayReport($fromDate,$toDate);
        $conversionsByDayReportData = $adwordsReporting->getCampaignConversionsByDayReport($fromDate,$toDate);
        $demographicsReportData = $adwordsReporting->getAgeGenderDeviceReport($fromDate,$toDate);
        $topKeywordsReportData = $adwordsReporting->getTopKeywordsReport($fromDate,$toDate);

        $topCampaigns = [];

        if ($campaigns = array_get($campaignReportData,'rows')) {
            $topCampaigns = collect($campaigns)
                        ->sortByDesc('Impressions')
                        ->values()
                        ->take(10)
                        ->all();
        }
        /**
         * Google analytics data fetching
         */


        $this->data = [
            'spend' => $campaignReportData['total']['Cost'],
            'impressions' => $campaignReportData['total']['Impressions'],
            'ctr' => $campaignReportData['total']['CTR'],
            'clicks' => $campaignReportData['total']['Clicks'],
            'avg_cpc' => $campaignReportData['total']['Avg. CPC'],
            'conversions' => $campaignReportData['total']['All conv.'],
            'spend_by_day' => $spendByDayReportData['rows'],
            'conversions_by_day' => $conversionsByDayReportData['rows'],
            'age_genders_devices' => $demographicsReportData,
            'top_keywords' => $topKeywordsReportData,
            'top_performing_campaigns' => $topCampaigns,
            'performance_by_country'=> $topCountriesReportData
        ];
        return $this;
    }


    public function get($type = 'raw')
    {
        if ($type == 'raw') {
            return $this->data;
        } else if ($type == 'email') {
            return $this->getEmailData();
        } else if ($type == 'mock') {
            return $this->getMockData();
        }
    }

    public function getEmailData()
    {
        if (!$this->data) {
            return;
        }

        $emailData = $this->data;

        if ($this->data['spend']) {
            $emailData['spend'] = number_format($this->data['spend'], 2);
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

        if ($this->data['age_genders_devices']) {
            $ageChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['age'],
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
                $this->data['age_genders_devices']['genders'],
                ['label-key' => 'Gender','data-key' => 'Impressions']
            );
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);


            $deviceChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['devices'],
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

            $emailData['age_genders_devices_chart_url'] = [
                'age' => $ageChartUrl,
                'genders' => $genderChartUrl,
                'devices' => $deviceChartUrl
            ];
           
        }
        $emailData['performance_by_country_chart_url'] = "";
        if ($this->data['performance_by_country']) {
            $mapData =  array_reduce($this->data['performance_by_country'],function ($result,$item) {
                $result[$item['CountryISO']] = $item['Clicks'];
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['performance_by_country_chart_url'] = $url;
        }

        $emailData['spend_and_conversions_by_day_chart_url'] = '#';
        if ($this->data['spend_by_day'] && $this->data['conversions_by_day']) {
            $chartData = $this->chartService->generateComboChartData(
                array_merge($this->data['spend_by_day'],$this->data['conversions_by_day']),
                'Day',
                ['All conv.','Cost']
            );
            $url = $this->chartService->getFusionChartImageUrl(
                $chartData['labels'],
                [
                    [
                        'label' => 'Spend',
                        'type' => 'line',
                        'data' => $chartData['dataset']['Cost'],
                        'borderColor' => '#1976d2',
                        'backgroundColor' => 'transparent'
                    ],
                    [
                        'label' => 'Conversions',
                        'type' => 'bar',
                        'data' => $chartData['dataset']['All conv.'],
                        'backgroundColor' => '#b71c1c',
                        'borderWidth' => 2
                    ],
        
                ],
                [
                    'title.text' => 'Spend vs Conversions By Day',
                    'scales.xAxes.0.barPercentage' => 0.2
                ]
            );
            if($url != '') {
                $emailData['spend_and_conversions_by_day_chart_url'] = $url;
            } else {
                $emailData['spend_and_conversions_by_day_chart_url'] = '#';

            }
        }
        return $emailData;
    }

    public function getMockData()
    {
        $period = new DatePeriod(
            new DateTime('2019-01-01'),
            new DateInterval('P1D'),
            new DateTime('2019-01-30')
        );

        $spendByDayReportData = [];
        $conversionsByDayReportData = [];
        $demographicsReportData = [];
        $topKeywordsReportData = [];
        $topCampaigns = [];
        $topCountriesReportData = [];

        foreach ($period as $date) {
            $spendByDayReportData[] = [
                'Cost' => rand(10,100),
                'Day' => $date->format('Y-m-d')
            ];
            $conversionsByDayReportData[] = [
                'All conv.' => rand(0,50),
                'Day' => $date->format('Y-m-d')
            ];    
        }

        $impressionsByAge = [
            [
                'AgeRange' => '18-24',
                'Impressions' => rand(1,100)
            ],
            [
                'AgeRange' => '25-34',
                'Impressions' => rand(1,100)
            ],
            [
                'AgeRange' => '35-44',
                'Impressions' => rand(1,100)
            ],
            [
                'AgeRange' => '45-54',
                'Impressions' => rand(1,100)
            ],
            [
                'AgeRange' => '55+',
                'Impressions' => rand(1,100)
            ],
        ];

        $impressionsByGender = [
            [
                'Gender' => 'male',
                'Impressions' => rand(1,100)
            ],
            [
                'Gender' => 'female',
                'Impressions' => rand(1,100)
            ],
        ];

        $impressionsByDevice = [
            [
                'Device' => 'Android',
                'Impressions' => rand(1,100)
            ],
            [
                'Device' => 'iOS',
                'Impressions' => rand(1,100)
            ],
            [
                'Device' => 'Linux',
                'Impressions' => rand(1,100)
            ],
            [
                'Device' => 'Windows',
                'Impressions' => rand(1,100)
            ],
        ];

        $demographicsReportData = [
            'age' => $impressionsByAge,
            'genders' => $impressionsByGender,
            'devices' => $impressionsByDevice
        ];

        foreach (range(0, 5) as $index) {
            $topKeywordsReportData[] = [
                'Keyword' => "keyword $index",
                'Clicks' => rand(1,100),
                'Impressions' => rand(100,200),
                'CTR' => mt_rand(10,20) / 10,
                'Avg. CPC' => mt_rand(10,20) / 10,
                'All conv.' => mt_rand(0,10),
                'Cost / all conv.' => mt_rand(10,20) / 10
            ];
            $topCampaigns[] = [
                'Campaign' => "Campaign $index",
                'Clicks' => rand(1,100),
                'Impressions' => rand(100,200),
                'CTR' => mt_rand(10,20) / 10,
                'Cost' => mt_rand(1,20),
                'All conv.' => mt_rand(0,10),
                'Cost / all conv.' => mt_rand(10,20) / 10
            ];
        }

        $countries = \PragmaRX\Countries\Package\Countries::where('geo.area','>',1000000)->all()->random(10);

        foreach ($countries as $country) {
            $topCountriesReportData[] = [
                'Clicks' => rand(1,100),
                'CountryISO' => $country->cca3,
                'CountryName' => $country->name->common
            ];
        }

        $this->data = [
            'spend' => mt_rand(100,200),
            'impressions' => mt_rand(30,100),
            'ctr' => mt_rand(10,20) / 10,
            'clicks' => mt_rand(1,50),
            'avg_cpc' => mt_rand(10,20) / 10,
            'conversions' => mt_rand(10,50),
            'spend_by_day' => $spendByDayReportData,
            'conversions_by_day' => $conversionsByDayReportData,
            'age_genders_devices' => $demographicsReportData,
            'top_keywords' => $topKeywordsReportData,
            'top_performing_campaigns' => $topCampaigns,
            'performance_by_country'=> $topCountriesReportData
        ];

        return $this->getEmailData();
    }

    public function invalidAccountsException()
    {
        return new InvalidArgumentException(implode(', ',$this->requiredAccountTypes)
            .' accounts are required for generating report');
    }
    
}

