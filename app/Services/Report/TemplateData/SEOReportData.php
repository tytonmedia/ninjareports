<?php

namespace App\Services\Report\TemplateData;

use App\Services\GoogleAnalyticsService;
use App\Services\ChartService;

/**
 * API Request Count
 * google analytics: 2
 * google analytics reporting : 1
 * google webmaster : 2
 * 
 */
class SEOReportData
{
    private $analyticsAccount = null;
    private $searchConsoleAccount = null;
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
        if (!array_has($accounts, ['google_analytics', 'google_search_console'])) {
            return;
        }

        $this->analyticsAccount = $accounts['google_analytics'];
        $this->searchConsoleAccount = $accounts['google_search_console'];
       
        return $this;
    }
    

    public function generate($fromDate,$toDate)
    {
        if (!$this->analyticsAccount && !$this->searchConsoleAccount) {
            return;
        }

        $analytics = $this->googleAnalyticsService->initAnalytics($this->analyticsAccount['access_token']);

        $profileId = $this->analyticsAccount['profile_id'];

        // orgainic traffic & sessions
        $organicSessionsResult = $analytics->data_ga->get(
            'ga:'.$profileId, 
            $fromDate,
            $toDate, 
            'ga:sessions',
            ['dimensions' => 'ga:date']
        );
        $organicSessionsReport = $this->googleAnalyticsService->parseGaData($organicSessionsResult);        
        $organicSessions = $this->googleAnalyticsService->getReportRows(
            $organicSessionsReport,
            [
                'ga:date' => 'date',
                'ga:sessions' => 'sessions',
            ],
            function ($entry) {
                $entry['date'] = substr($entry['date'], 0, 4).'-'.substr($entry['date'], 5, 6).'-'.substr($entry['date'], 7, 8);
                return $entry;
            }
        );
        // overall analytics data
        $analyticsResults = $analytics->data_ga->get(
            'ga:'.$profileId, 
            $fromDate,
            $toDate, 
            'ga:sessions,ga:pageviews,ga:avgTimeOnPage,ga:pageviewsPerSession'
        );
        $analyticsResultsTotal = $analyticsResults->totalsForAllResults;

        // top organic pages
        $topPagesResult = $analytics->data_ga->get(
                'ga:'.$profileId, 
                $fromDate,
                $toDate, 
                'ga:sessions,ga:pageviews,ga:avgTimeOnPage,ga:bounceRate',
                [
                    'dimensions' => 'ga:pagePath',
                    'sort' => '-ga:sessions',
                    'max-results' => 10
                ]
            );

        $topPagesReport = $this->googleAnalyticsService->parseGaData($topPagesResult);
        $topPages = $this->googleAnalyticsService->getReportRows(
                $topPagesReport,
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
        
        // organic sessions by source
        // $analyticsSessionsBySource = $analytics->data_ga->get(
        //     'ga:'.$profileId, 
        //     $fromDate,
        //     $toDate, 
        //     'ga:sessions',
        //     [
        //         'dimensions' => 'ga:source',
        //         'sort' => '-ga:sessions'
        //     ]
        // );
        // $totalSessions = $analyticsSessionsBySource->totalsForAllResults['ga:sessions'];
        // $sessionsBySource = [];
        // foreach ($analyticsSessionsBySource->rows as $row) {
        //     $sessionsBySource[] = [
        //         'source' => $row[0],
        //         'sessions' => $row[1],
        //         'percentage' => ($row[1]/$totalSessions) * 100
        //     ];
        // }

        // age_gender_device, organic_traffic_by_country (batch request)
        $analyticsReporting = $this->googleAnalyticsService->initAnalyticsReporting($this->analyticsAccount['access_token']);
        
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($fromDate);
        $dateRange->setEndDate($toDate);

        $reportRequest = new \Google_Service_AnalyticsReporting_ReportRequest();
        $reportRequest->setViewId("ga:$profileId");
        $reportRequest->setDateRanges($dateRange);

        $sessionAscOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $sessionAscOrder->setFieldName('ga:sessions');
        $sessionAscOrder->setSortOrder('DESCENDING');

        $sessionsMetric = new \Google_Service_AnalyticsReporting_Metric();
        $sessionsMetric->setExpression('ga:sessions');

        $usersMetric = new \Google_Service_AnalyticsReporting_Metric();
        $usersMetric->setExpression('ga:users');

        // $pageviewsMetric = new \Google_Service_AnalyticsReporting_Metric();
        // $pageviewsMetric->setExpression('ga:pageviews');

        // $avgTimeOnPageMetric = new \Google_Service_AnalyticsReporting_Metric();
        // $avgTimeOnPageMetric->setExpression('ga:avgTimeOnPage');

        // $pageviewsPerSessionMetric = new \Google_Service_AnalyticsReporting_Metric();
        // $pageviewsPerSessionMetric->setExpression('ga:pageviewsPerSession');

        $countryDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $countryDimension->setName('ga:country');

        $countryISODimension = new \Google_Service_AnalyticsReporting_Dimension();
        $countryISODimension->setName('ga:countryIsoCode');

        $OSDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $OSDimension->setName('ga:operatingSystem');

        $userAgeBracketDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $userAgeBracketDimension->setName('ga:userAgeBracket');

        $userGenderDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $userGenderDimension->setName('ga:userGender');

        $sourceDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sourceDimension->setName('ga:source');

        // age_genders_devices
        $usersByAgeReportRequest = clone $reportRequest;
        $usersByAgeReportRequest->setMetrics([$usersMetric]);
        $usersByAgeReportRequest->setDimensions([$userAgeBracketDimension]);

        $usersByGenderReportRequest = clone $reportRequest;
        $usersByGenderReportRequest->setMetrics([$usersMetric]);
        $usersByGenderReportRequest->setDimensions([$userGenderDimension]);

        $usersByPlatformReportRequest = clone $reportRequest;
        $usersByPlatformReportRequest->setMetrics([$usersMetric]);
        $usersByPlatformReportRequest->setDimensions([$OSDimension]);

        // organic_traffic_by_country
        $sessionsByCountryReportRequest = clone $reportRequest;
        $sessionsByCountryReportRequest->setMetrics([$sessionsMetric]);
        $sessionsByCountryReportRequest->setDimensions([$countryDimension,$countryISODimension]);
        $sessionsByCountryReportRequest->setOrderBys($sessionAscOrder);
        $sessionsByCountryReportRequest->setPageSize(10);

        // organic_sessions_by_source

        $sessionsBySourceReportRequest =  clone $reportRequest;
        $sessionsBySourceReportRequest->setMetrics([$sessionsMetric]);
        $sessionsBySourceReportRequest->setDimensions([$sourceDimension]);
        $sessionsBySourceReportRequest->setOrderBys($sessionAscOrder);

        //organic_sessions, organic_pageviews, time_on_page, pages_per_visit

        // $generalReportRequest = clone $reportRequest;
        // $generalReportRequest->setMetrics([$sessionsMetric,$pageviewsMetric,$avgTimeOnPageMetric,$pageviewsMetric]);


        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([
            $usersByGenderReportRequest,
            $usersByAgeReportRequest,
            $usersByPlatformReportRequest,
            $sessionsByCountryReportRequest,
            $sessionsBySourceReportRequest,
        ]);
        
        $result = $analyticsReporting->reports->batchGet($body);
        $parsedReports = $this->googleAnalyticsService->parseReports($result->reports);

        $sessionsBySource = $this->googleAnalyticsService->getReportRows(
                $parsedReports[4],['ga:source' => 'source', 'ga:sessions' => 'sessions']);

        $sessionsByCountry = $this->googleAnalyticsService->getReportRows(
            $parsedReports[3],['ga:country' => 'country', 'ga:countryIsoCode'=> 'country_code','ga:sessions' => 'sessions']);

        $usersByGender = $this->googleAnalyticsService->getReportRows(
            $parsedReports[0],['ga:userGender' => 'gender', 'ga:users' => 'users']);

        $usersByAge = $this->googleAnalyticsService->getReportRows(
            $parsedReports[1],['ga:userAgeBracket' => 'age', 'ga:users' => 'users']);

        $usersByPlatform = $this->googleAnalyticsService->getReportRows(
            $parsedReports[2],['ga:operatingSystem' => 'platform', 'ga:users' => 'users']);

        /** 
         * 
         * search console data fetching 
         * 
         * */

        $searchConsole = $this->initializeSearchConsole(
                main_path('google-search.json'),$this->searchConsoleAccount['access_token']);

        $siteUrl = $this->searchConsoleAccount['site_url'];

        $generalQuery = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $generalQuery->setStartDate($fromDate);
        $generalQuery->setEndDate($toDate);

        // $topPagesQuery = clone $generalQuery;
        // $topPagesQuery->setDimensions(['page']);
        // $topPagesQuery->setRowLimit(5);

        $topKeywordsQuery = clone $generalQuery;
        $topKeywordsQuery->setDimensions(['query']);
        $topKeywordsQuery->setRowLimit(10);
        
        // $topPages = $searchConsole->searchanalytics->query($siteUrl,$topPagesQuery)->getRows();
        $topKeywords=[];
        $topKeywordRows = $searchConsole->searchanalytics->query($siteUrl,$topKeywordsQuery)->getRows();
        foreach ($topKeywordRows as $row) {
           $topKeywords[] = [
               'keyword' => $row->keys[0],
               'clicks' => $row->clicks,
               'impressions' => $row->impressions,
               'ctr' => $row->ctr,
               'position' => $row->position
           ];
        }
        $generalData = $searchConsole->searchanalytics->query($siteUrl,$generalQuery)->getRows();
        
        $this->data = [
            'organic_sessions' => $analyticsResultsTotal['ga:sessions'],
            'organic_pageviews' => $analyticsResultsTotal['ga:pageviews'],
            'organic_impressions' => $generalData? $generalData[0]->impressions : null,
            'time_on_page' => gmdate('H:i:s',$analyticsResultsTotal['ga:avgTimeOnPage']),
            'organic_revenue' => null,
            'pages_per_visit' => round($analyticsResultsTotal['ga:pageviewsPerSession']),
            'organic_traffic_and_session' => $organicSessions,
            'organic_sessions_by_source' => $sessionsBySource,
            'organic_traffic_by_country' => $sessionsByCountry,
            'age_genders_devices' => [
                'age' => $usersByAge,
                'genders' => $usersByGender,
                'devices' =>$usersByPlatform
            ],
            'top_organic_pages' => $topPages,
            'top_organic_keywords' => $topKeywords,
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

            $ageChartData = array_reduce($this->data['age_genders_devices']['age'],function ($result,$item) {
                    $result['labels'][] = $item['age'];
                    $result['data'][] = $item['users'];
                    return $result;
                },['labels'=>[],'data'=>[]]);
            $ageChartUrl= $this->chartService->getBarChartImageUrl(
                $ageChartData['labels'],
                $ageChartData['data'],
                [
                    'bar-color' => 'rgb(0, 191, 255)',
                    'title' => 'Users by Age'
                ]
            );

            $genderChartData = array_reduce($this->data['age_genders_devices']['genders'],function ($result,$item) {
                $result[] = [$item['gender'],$item['users']];
                return $result;
            },[]);
            $genderChartUrl = $this->chartService->getDonutChartImageUrl($genderChartData);

            $deviceChartData = array_reduce($this->data['age_genders_devices']['devices'],function ($result,$item) {
                    $result['labels'][] = $item['platform'];
                    $result['data'][] = $item['users'];
                    return $result;
                },['labels'=>[],'data'=>[]]);
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

        if ($this->data['organic_traffic_by_country']) {
            $countries = new \PragmaRX\Countries\Package\Countries;
            $mapData =  array_reduce($this->data['organic_traffic_by_country'],function ($result,$item) use($countries) {
                $cca3Code = $countries->where('cca2',$item['country_code'])->first()->cca3;
                if ($cca3Code) {
                    $result[$cca3Code] = $item['sessions']; 
                }
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            // $emailData['organic_traffic_by_country'] = "<img src='$url' style='width: 100%;height: auto;' >";
            $emailData['organic_traffic_by_country_chart_url'] = $url;
            /*
            ob_start();
            ?>
            <div>
                <table style="width: 100%;
                        max-width: 100%;
                        margin-bottom: 20px;
                        border-spacing: 0;
                        border-collapse: collapse;" class="table-custom">
                <tr>
                    <th>Country</th>
                    <th>Sessions</th>
                </tr>
                <?php foreach($this->data['organic_traffic_by_country'] as $row): ?>
                    <tr>
                        <td width="230"><?= $row['country'] ?> </td>
                        <td><?= $row['sessions'] ?></td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>
            <?php
            $emailData['organic_traffic_by_country_table_html'] = ob_get_clean();
            */
        }

        if ($this->data['organic_sessions_by_source']) {
            $sessionsBySourceChartData = array_reduce($this->data['organic_sessions_by_source'],function ($result,$item) {
                $result[] = [$item['source'],$item['sessions']];
                return $result;
            },[]);
            $sessionsBySourceChartUrl = $this->chartService->getDonutChartImageUrl($sessionsBySourceChartData);
            // $emailData['organic_sessions_by_source'] = "<img src='$sessionsBySourceChartUrl' style='width: 100%;height: auto;' >";
            $emailData['organic_sessions_by_source_chart_url'] = $sessionsBySourceChartUrl;
        }

        if ($this->data['top_organic_pages']) {
            /*
            ob_start();
            ?>
            <div>
                <table style="width: 100%;
                        max-width: 100%;
                        margin-bottom: 20px;
                        border-spacing: 0;
                        border-collapse: collapse;" class="table-custom">
                <tr>
                    <th>URL</th>
                    <th>Sessions</th>
                    <th>Pageviews</th>
                    <th>Avg Time on Page</th>
                    <th>Bounce Rate (%)</th>
                    <th>Revenue</th>
                </tr>
                <?php foreach($this->data['top_organic_pages'] as $row): ?>
                    <tr>
                        <td width="230"><?= $row['page'] ?> </td>
                        <td><?= $row['sessions'] ?></td>
                        <td><?= $row['pageviews'] ?></td>
                        <td><?= $row['avg_time_on_page'] ?></td>
                        <td><?= $row['bounce_rate'] ?></td>
                        <td>0.00</td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>
            <?php
            $topOrganicPagesHtml = ob_get_clean();
            // $emailData['top_organic_pages_html'] = $topOrganicPagesHtml;
            */
        }

        if ($this->data['top_organic_keywords']) {
            /*
            ob_start();
            ?>
            <div>
                <table style="width: 100%;
                        max-width: 100%;
                        margin-bottom: 20px;
                        border-spacing: 0;
                        border-collapse: collapse;" class="table-custom">
                <tr>
                    <th>Keyword</th>
                    <th>Click</th>
                    <th>Impression</th>
                    <th>CTR</th>
                    <th>Position</th>
                </tr>
                <?php foreach($this->data['top_organic_keywords'] as $row): ?>
                    <tr>
                        <td width="230"> <?= $row->keys[0] ?> </td>
                        <td><?= $row->clicks ?></td>
                        <td><?= $row->impressions ?></td>
                        <td><?= $row->ctr ?></td>
                        <td><?= $row->position ?></td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>
            <?php
            $topOrganicKeywordsHtml = ob_get_clean();
            // $emailData['top_organic_keywords_html'] = $topOrganicKeywordsHtml;
            */
        }

        if ($this->data['organic_traffic_and_session']) {
            $chartData = array_map(function ($item) {
                return [
                 'x' => $item['date'],
                 'y' => $item['sessions']
                ];
            },$this->data['organic_traffic_and_session']);
            $organicSessionsChartUrl = $this->chartService->getLineTimeseriesChartImageUrl($chartData,
                    [
                    'title' => 'Orgainic Sessions',
                    'label-name' => 'sessions',
                    'line-color' => 'rgb(255, 215, 0)',
                    'line-area-color' => 'rgba(255, 215, 0,0.2)'
                    ]);
            $emailData['organic_traffic_and_session_chart_url'] = $organicSessionsChartUrl;
        }

        return $emailData;
    }


    public function initializeSearchConsole($configFilePath,$accessToken)
    {
        $client = new \Google_Client();
        $client->setAuthConfig($configFilePath);
        $client->addScope([\Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Webmasters::WEBMASTERS_READONLY]);
        $client->setAccessToken($accessToken);
        $searchConsole = new \Google_Service_Webmasters($client);
        return $searchConsole;
    }


    public function buildHtmlTable($rows)
    {
        $keys = array_keys($rows[0]);
        ob_start();
        ?>
        <div>
            <table style="width: 100%;
                    max-width: 100%;
                    margin-bottom: 20px;
                    border-spacing: 0;
                    border-collapse: collapse;" class="table-custom">
            <tr>
                <?php foreach($keys as $key): ?>
                    <th><?= $key ?></th>
                <?php endforeach ?>
            </tr>
            <?php foreach($rows as $row): ?>
                <tr>
                    <?php foreach($row as $cell): ?>
                        <td><?= $cell ?></td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
            </table>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }


}

