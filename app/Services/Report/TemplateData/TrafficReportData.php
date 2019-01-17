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
        $sourceDimension = $this->googleAnalyticsService->buildDimensionByName('ga:source');

        $usersBySourceReportRequest = clone $reportRequest;
        $usersBySourceReportRequest->setMetrics([$usersMetric]);
        $usersBySourceReportRequest->setDimensions([$sourceDimension]);

        // users_by_country
        $countryDimension = $this->googleAnalyticsService->buildDimensionByName('ga:country');
        $countryISODimension = $this->googleAnalyticsService->buildDimensionByName('ga:countryIsoCode');

        $usersDescOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $usersDescOrder->setFieldName('ga:users');
        $usersDescOrder->setSortOrder('DESCENDING');

        $usersByCountryReportRequest = clone $reportRequest;
        $usersByCountryReportRequest->setMetrics([$usersMetric]);
        $usersByCountryReportRequest->setDimensions([$countryDimension,$countryISODimension]);
        $usersByCountryReportRequest->setPageSize(15);
        $usersByCountryReportRequest->setOrderBys($usersDescOrder);

        // top_pages
        $topPagesReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:sessions'],
                ['expression' => 'ga:pageviews'],
                ['expression' => 'ga:avgTimeOnPage'],
                ['expression' => 'ga:bounceRate']
            ]);

        $pagePathDimension = $this->googleAnalyticsService->buildDimensionByName('ga:pagePath');

        $sessionsDescOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $sessionsDescOrder->setFieldName('ga:sessions');
        $sessionsDescOrder->setSortOrder('DESCENDING');

        $topPagesReportRequest = clone $reportRequest;
        $topPagesReportRequest->setMetrics($topPagesReportMetrics);
        $topPagesReportRequest->setDimensions([$pagePathDimension]);
        $topPagesReportRequest->setPageSize(10);
        $topPagesReportRequest->setOrderBys($sessionsDescOrder);

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

        $usersBySource = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_source'],['ga:source' => 'source', 'ga:users' => 'users']);

        $usersByCountry = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_country'],['ga:country' => 'country','ga:countryIsoCode'=> 'country_code','ga:users' => 'users']);

        $topPages = $this->googleAnalyticsService->getReportRows(
                $parsedReports['top_pages'],
                [
                    'ga:pagePath' => 'page',
                    'ga:sessions' => 'sessions',
                    'ga:pageviews' => 'pageviews',
                    'ga:avgTimeOnPage' => 'avg_time_on_page',
                    'ga:bounceRate' => 'bounce_rate'
                ],
                function ($entry) {
                    $entry['avg_time_on_page'] = gmdate('H:i:s',$entry['avg_time_on_page']);
                    $entry['bounce_rate'] = round($entry['bounce_rate']);
                    return $entry;
                }
            );

        $this->data = [
            'users' => $generalReport['ga:users'],
            'pageviews' => $generalReport['ga:pageviews'],
            'pages_per_visit' => round($generalReport['ga:pageviewsPerSession']),
            'bounce_rate' => round($generalReport['ga:bounceRate']),
            'new_visitors' => $generalReport['ga:newUsers'],
            'avg_time_on_site' => gmdate('H:i:s',$generalReport['ga:avgTimeOnPage']),
            'avg_page_load_time' => round($generalReport['ga:avgPageLoadTime'],2),
            'avg_server_response_time' => round($generalReport['ga:avgServerResponseTime'],2),
            'avg_page_download_time' => round($generalReport['ga:avgPageDownloadTime'],2),
            'users_by_country'=> $usersByCountry,
            'top_pages' => $topPages,
            'visitors_by_source' => $usersBySource,
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
        }
    }

    public function getEmailData()
    {
        if (!$this->data) {
            return;
        }

        $emailData = $this->data;

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

            $emailData['age_genders_devices'] = "<div>
                <div style='float: left; width: 33%;'>
                    <img style='width: 100%;height: auto;' src='$ageChartUrl'>
                </div>
                <div style='display: inline-block; width: 33%;'>
                    <img style='width: 100%;height: auto;' src='$genderChartUrl'>
                </div>
                <div style='float: right; width: 33%;'>
                    <img style='width: 100%;height: auto;' src='$deviceChartUrl'>
                </div>
            </div>";
           
        }

        if ($this->data['visitors_by_source']) {
            $usersBySourceChartData = $this->chartService->generateDonutChartData(
                $this->data['visitors_by_source'],
                ['label-key' => 'source','data-key' => 'users']
            );
            $usersBySourceChartUrl = $this->chartService->getDonutChartImageUrl($usersBySourceChartData);
            $emailData['visitors_by_source'] = "<img src='$usersBySourceChartUrl' style='width: 100%;height: auto;' >";
        }

        if ($this->data['top_pages']) {
            ob_start();
            ?>
            <div>
                <table>
                <tr>
                    <th>URL</th>
                    <th>Sessions</th>
                    <th>Pageviews</th>
                    <th>Avg Time on Page</th>
                    <th>Bounce Rate (%)</th>
                </tr>
                <?php foreach($this->data['top_pages'] as $row): ?>
                    <tr>
                        <td><?= $row['page'] ?> </td>
                        <td><?= $row['sessions'] ?></td>
                        <td><?= $row['pageviews'] ?></td>
                        <td><?= $row['avg_time_on_page'] ?></td>
                        <td><?= $row['bounce_rate'] ?></td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>
            <?php
            $topPagesHtml = ob_get_clean();
            $emailData['top_pages'] = $topPagesHtml;
        }

        if ($this->data['users_by_country']) {
            $countries = new \PragmaRX\Countries\Package\Countries;
            $mapData =  array_reduce($this->data['users_by_country'],function ($result,$item) use($countries) {
                $cca3Code = $countries->where('cca2',$item['country_code'])->first()->cca3;
                if ($cca3Code) {
                    $result[$cca3Code] = $item['users']; 
                }
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['users_by_country'] = "<img src='$url' style='width: 100%;height: auto;' >";
        }

        return $emailData;
    }
}


