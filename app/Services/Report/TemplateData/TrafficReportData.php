<?php

namespace App\Services\Report\TemplateData;

use App\Services\GoogleAnalyticsService;
use App\Services\GoogleAnalyticsHelpers\Reporting\Batchman;
use App\Services\ChartService;

 
class TrafficReportData
{
    private $requiredAccountTypes = ['google_analytics','google_search_console'];
    private $accounts = null;
    private $data = null;

    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        ChartService $chartService
    ) { 
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->chartService = $chartService;
    }
    /*
        accounts argumnent structure
        [
            'google_analytics' => [
                'profile_id' => '',
                'access_token' => ''
            ],
            'google_search_console' => [
                'site_url' => '',
                'access_token' => ''
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

        $analyticsAccessToken = $this->accounts['google_analytics']['access_token'];
        $profileId = $this->accounts['google_analytics']['profile_id'];

        $analyticsReporting = $this->googleAnalyticsService->initAnalyticsReporting($analyticsAccessToken);

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($fromDate);
        $dateRange->setEndDate($toDate);

        $reportRequest = new \Google_Service_AnalyticsReporting_ReportRequest();
        $reportRequest->setViewId("ga:$profileId");
        $reportRequest->setDateRanges($dateRange);

        $pageviewsDescOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $pageviewsDescOrder->setFieldName('ga:pageviews');
        $pageviewsDescOrder->setSortOrder('DESCENDING');

        // general analytics data
        $generalReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:users'],
                ['expression' => 'ga:pageviews'],
                ['expression' => 'ga:pageviewsPerSession'],
                ['expression' => 'ga:bounceRate'],
                ['expression' => 'ga:newUsers'],
                ['expression' => 'ga:avgTimeOnPage'],
                ['expression' => 'ga:avgPageLoadTime'],
                ['expression' => 'ga:avgServerResponseTime'],
                ['expression' => 'ga:avgPageDownloadTime'],
            ]);

        $generalReportRequest = clone $reportRequest;
        $generalReportRequest->setMetrics($generalReportMetrics);

        // age_genders_devices
        $usersMetric = $generalReportMetrics[0];
        $userAgeBracketDimension = $this->googleAnalyticsService->buildDimensionByName('ga:userAgeBracket');
        $userGenderDimension = $this->googleAnalyticsService->buildDimensionByName('ga:userGender');
        $OSDimension = $this->googleAnalyticsService->buildDimensionByName('ga:operatingSystem');

        $usersByAgeReportRequest = clone $reportRequest;
        $usersByAgeReportRequest->setMetrics([$usersMetric]);
        $usersByAgeReportRequest->setDimensions([$userAgeBracketDimension]);

        $usersByGenderReportRequest = clone $reportRequest;
        $usersByGenderReportRequest->setMetrics([$usersMetric]);
        $usersByGenderReportRequest->setDimensions([$userGenderDimension]);

        $usersByPlatformReportRequest = clone $reportRequest;
        $usersByPlatformReportRequest->setMetrics([$usersMetric]);
        $usersByPlatformReportRequest->setDimensions([$OSDimension]);

        // visitors_by_source
        $usersBySourceReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:pageviews'],
                ['expression' => 'ga:avgTimeOnPage'],
                ['expression' => 'ga:bounceRate']
            ]);
        $sourceDimension = $this->googleAnalyticsService->buildDimensionByName('ga:source');

        $usersBySourceReportRequest = clone $reportRequest;
        $usersBySourceReportRequest->setMetrics($usersBySourceReportMetrics);
        $usersBySourceReportRequest->setDimensions([$sourceDimension]);
        $usersBySourceReportRequest->setPageSize(10);
        $usersBySourceReportRequest->setOrderBys($pageviewsDescOrder);

        // users_by_country
        $countryDimension = $this->googleAnalyticsService->buildDimensionByName('ga:country');
        $countryISODimension = $this->googleAnalyticsService->buildDimensionByName('ga:countryIsoCode');

        $usersDescOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $usersDescOrder->setFieldName('ga:users');
        $usersDescOrder->setSortOrder('DESCENDING');

        $usersByCountryReportRequest = clone $reportRequest;
        $usersByCountryReportRequest->setMetrics([$usersMetric]);
        $usersByCountryReportRequest->setDimensions([$countryDimension,$countryISODimension]);
        $usersByCountryReportRequest->setPageSize(10);
        $usersByCountryReportRequest->setOrderBys($usersDescOrder);

        // top_pages
        $topPagesReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:pageviews'],
                ['expression' => 'ga:avgTimeOnPage'],
                ['expression' => 'ga:bounceRate']
            ]);

        $pagePathDimension = $this->googleAnalyticsService->buildDimensionByName('ga:pagePath');

        $topPagesReportRequest = clone $reportRequest;
        $topPagesReportRequest->setMetrics($topPagesReportMetrics);
        $topPagesReportRequest->setDimensions([$pagePathDimension]);
        $topPagesReportRequest->setPageSize(10);
        $topPagesReportRequest->setOrderBys($pageviewsDescOrder);

        // fetching data 
        $reportGroup = (new Batchman($analyticsReporting))
                        ->setReportRequestGroup([
                            'general' => $generalReportRequest,
                            'users_by_age' => $usersByAgeReportRequest,
                            'users_by_gender' => $usersByGenderReportRequest,
                            'users_by_platform' => $usersByPlatformReportRequest,
                            'users_by_source' => $usersBySourceReportRequest,
                            'users_by_country' => $usersByCountryReportRequest,
                            'top_pages' => $topPagesReportRequest
                        ])
                        ->getAll();
                        
        $parsedReports = $this->googleAnalyticsService->parseReportGroup($reportGroup);

        $generalReport = $parsedReports['general']['total'];
        
        $usersByAge = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_age'],['ga:userAgeBracket' => 'age', 'ga:users' => 'users']);

        $usersByGender = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_gender'],['ga:userGender' => 'gender', 'ga:users' => 'users']);

        $usersByPlatform = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_platform'],['ga:operatingSystem' => 'platform', 'ga:users' => 'users']);

        $visitorsBySource = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_source'],
            [
                'ga:source' => 'source', 
                'ga:pageviews' => 'pageviews',
                'ga:avgTimeOnPage' => 'avg_time_on_page',
                'ga:bounceRate' => 'bounce_rate'
            ],
            function ($entry) {
                $entry['avg_time_on_page'] = gmdate('H:i:s',$entry['avg_time_on_page']);
                $entry['bounce_rate'] = round($entry['bounce_rate']);
                $entry['revenue'] = null;
                return $entry;
            }
        );

        $usersByCountry = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_country'],['ga:country' => 'country','ga:countryIsoCode'=> 'country_code','ga:users' => 'users']);

        $topPages = $this->googleAnalyticsService->getReportRows(
                $parsedReports['top_pages'],
                [
                    'ga:pagePath' => 'page',
                    'ga:pageviews' => 'pageviews',
                    'ga:avgTimeOnPage' => 'avg_time_on_page',
                    'ga:bounceRate' => 'bounce_rate'
                ],
                function ($entry) {
                    $entry['avg_time_on_page'] = gmdate('H:i:s',$entry['avg_time_on_page']);
                    $entry['bounce_rate'] = round($entry['bounce_rate']);
                    $entry['revenue'] = null;
                    return $entry;
                }
            );

        $this->data = [
            'domain' => $profileId,
            'users' => $generalReport['ga:users'],
            'pageviews' => $generalReport['ga:pageviews'],
            'pages_per_visit' => $generalReport['ga:pageviewsPerSession'],
            'bounce_rate' => $generalReport['ga:bounceRate'],
            'new_visitors' => $generalReport['ga:newUsers'],
            'avg_time_on_site' => $generalReport['ga:avgTimeOnPage'],
            'avg_page_load_time' => $generalReport['ga:avgPageLoadTime'],
            'avg_server_response_time' => $generalReport['ga:avgServerResponseTime'],
            'avg_page_download_time' => $generalReport['ga:avgPageDownloadTime'],
            'users_by_country'=> $usersByCountry,
            'top_pages' => $topPages,
            'visitors_by_source' => $visitorsBySource,
            'age_genders_devices' => [
                'age' => $usersByAge,
                'genders' => $usersByGender,
                'devices' =>$usersByPlatform
            ]
        ];
        return $this;
    }

    public function get($type = 'raw')
    {
        if ($type == 'raw') {
            return $this->data;
        } else if ($type == 'email') {
            return $this->getEmailData();
        } elseif ($type == 'mock') {
            return $this->getMockData();
        }
    }

    public function getEmailData()
    {
        if (!$this->data) {
            return;
        }

        $emailData = $this->data;

        if ($this->data['users']) {
            $emailData['users'] = number_format($this->data['users']);
        }

        if ($this->data['pageviews']) {
            $emailData['pageviews'] = number_format($this->data['pageviews']);
        }
        
        if ($this->data['pages_per_visit']) {
            $emailData['pages_per_visit'] = number_format($this->data['pages_per_visit'], 2);
        }

        if ($this->data['bounce_rate']) {
            $emailData['bounce_rate'] = number_format($this->data['bounce_rate'], 2, '.', '').'%';
        }

        if ($this->data['new_visitors']) {
            $emailData['new_visitors'] = number_format($this->data['new_visitors']);
        }

        if ($this->data['avg_time_on_site']) {
            $emailData['avg_time_on_site'] = gmdate('i:s',$this->data['avg_time_on_site']);
        }

        if ($this->data['avg_page_load_time']) {
            $emailData['avg_page_load_time'] = round($this->data['avg_page_load_time'],2);
        }

        if ($this->data['avg_server_response_time']) {
            $emailData['avg_server_response_time'] = round($this->data['avg_server_response_time'],2);
        }

        if ($this->data['avg_page_download_time']) {
            $emailData['avg_page_download_time'] = round($this->data['avg_page_download_time'],2);
        }

        if ($this->data['age_genders_devices']) {
            $ageChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['age'],
                ['label-key' => 'age','data-key' => 'users']
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
                ['label-key' => 'gender','data-key' => 'users']
            );
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);


            $deviceChartData = $this->chartService->generateBarChartData(
                $this->data['age_genders_devices']['devices'],
                ['label-key' => 'platform','data-key' => 'users']
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

        if ($this->data['users_by_country']) {
            $countries = new \PragmaRX\Countries\Package\Countries;
            $mapData =  array_reduce($this->data['users_by_country'],function ($result,$item) use($countries) {
                $country = $countries->where('cca2',$item['country_code'])->first();
                if ($country->isNotEmpty()) {
                    $result[$country->cca3] = $item['users']; 
                }
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['users_by_country_chart_url'] = $url;
        }

        return $emailData;
    }

    public function getMockData()
    {
        $usersByCountry  = [];
        $topPages = [];
        $visitorsBySource = [];
        $usersByAge = [];
        $usersByGender = [];
        $usersByPlatform = [];

        $countries = \PragmaRX\Countries\Package\Countries::where('geo.area','>',1000000)->all()->random(10);

        foreach ($countries as $country) {
            $usersByCountry[] = [
                'users' => rand(1,100),
                'country_code' => $country->cca2,
                'country' => $country->name->common
            ];
        }

        foreach (range(0, 5) as $index) {
            $topPages[] = [
                'page' => "page $index",
                'pageviews' => rand(1,100),
                'avg_time_on_page' => '00:00',
                'bounce_rate' => mt_rand(100,200) / 10,
            ];
        }

        foreach (range(0, 10) as $index) {
            $visitorsBySource[] = [
                'pageviews' => rand(1,100),
                'avg_time_on_page' => '00:00',
                'bounce_rate' => mt_rand(100,200) / 10,
                'source' => "source $index"
            ];
        }

        $usersByAge = [
            [
                'age' => '18-24',
                'users' => rand(1,100)
            ],
            [
                'age' => '25-34',
                'users' => rand(1,100)
            ],
            [
                'age' => '35-44',
                'users' => rand(1,100)
            ],
            [
                'age' => '45-54',
                'users' => rand(1,100)
            ],
            [
                'age' => '55+',
                'users' => rand(1,100)
            ],
        ];

        $usersByGender = [
            [
                'gender' => 'male',
                'users' => rand(1,100)
            ],
            [
                'gender' => 'female',
                'users' => rand(1,100)
            ],
        ];

        $usersByPlatform = [
            [
                'platform' => 'Android',
                'users' => rand(1,100)
            ],
            [
                'platform' => 'iOS',
                'users' => rand(1,100)
            ],
            [
                'platform' => 'Linux',
                'users' => rand(1,100)
            ],
            [
                'platform' => 'Windows',
                'users' => rand(1,100)
            ],
        ];


        $this->data = [
            'users' => mt_rand(50,200),
            'pageviews' => mt_rand(200,500),
            'pages_per_visit' => mt_rand(10,20) / 10 ,
            'bounce_rate' =>  mt_rand(100,200) / 10 ,
            'new_visitors' => mt_rand(1,50),
            'avg_time_on_site' => time(),
            'avg_page_load_time' => mt_rand(20,30) / 10,
            'avg_server_response_time' => mt_rand(20,30) / 10,
            'avg_page_download_time' => mt_rand(20,30) / 10,
            'users_by_country'=> $usersByCountry,
            'top_pages' => $topPages,
            'visitors_by_source' => $visitorsBySource,
            'age_genders_devices' => [
                'age' => $usersByAge,
                'genders' => $usersByGender,
                'devices' =>$usersByPlatform
            ]
        ];

        return $this->getEmailData();
    }
}


