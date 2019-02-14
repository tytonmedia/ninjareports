<?php

namespace App\Services\Report\TemplateData;

use App\Services\GoogleAnalyticsService;
use App\Services\GoogleAdwordsReporting;
use App\Services\GoogleAnalyticsHelpers\Reporting\Batchman;
use App\Services\ChartService;
use InvalidArgumentException;

class EcommerceReportData {

    private $requiredAccountTypes = ['google_analytics','google_adwords','facebook'];
    private $accounts = null;
    private $data = null;

    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        GoogleAdwordsReporting $googleAdwordsReporting,
        ChartService $chartService
        ) {
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->googleAdwordsReporting = $googleAdwordsReporting;
        $this->chartService = $chartService;        
    }

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

        $analyticsAccessToken = $this->accounts['google_analytics']['access_token'];
        $profileId = $this->accounts['google_analytics']['profile_id'];
        $analytics = $this->googleAnalyticsService->initAnalytics($analyticsAccessToken);
        $analyticsReporting = $this->googleAnalyticsService->initAnalyticsReporting($analyticsAccessToken);

        $adwordsAccessToken = $this->accounts['google_adwords']['access_token'];
        $adwordsCustomerId = $this->accounts['google_adwords']['client_customer_id'];
        $adwordsReporting = $this->googleAdwordsReporting->initSession($adwordsAccessToken,$adwordsCustomerId);

        $facebookAccessToken = $this->accounts['facebook']['access_token'];
        $facebookAccountId = $this->accounts['facebook']['account_id'];


        $facebookAdsReporting = app('FacebookAdsReporting')
                ->init($facebookAccessToken,$facebookAccountId)
                ->betweenDates($fromDate,$toDate);

        $accountOverview = $facebookAdsReporting->accountOverview();
        $campaignReportData = $adwordsReporting->getCampaignReport($fromDate,$toDate);

        $totalClicks = array_get($campaignReportData,'total.Clicks',0) + array_get($accountOverview,'data.0.clicks',0);
        $totalSpend = array_get($campaignReportData,'total.Cost',0) + array_get($accountOverview,'data.0.spend',0);

        $totalCTR = 0;
        $adwordsCTR = floatval(array_get($campaignReportData,'total.CTR',0));
        $fbCTR = floatval(array_get($accountOverview,'data.0.ctr',0));

        if ($adwordsCTR > 0) {
            $totalCTR = $adwordsCTR;
        } elseif ($fbCTR > 0) {
            $totalCTR = $fbCTR;
        } 
        if ($adwordsCTR > 0 && $fbCTR > 0) {
           $totalCTR = ((($adwordsCTR / 100) + ($fbCTR / 100)) / 2) * 100;
        }

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($fromDate);
        $dateRange->setEndDate($toDate);

        $reportRequest = new \Google_Service_AnalyticsReporting_ReportRequest();
        $reportRequest->setViewId("ga:$profileId");
        $reportRequest->setDateRanges($dateRange);

        //  total revenue and transactions (general)
        $generalReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:itemRevenue'],
                ['expression' => 'ga:transactions'],
                ['expression' => 'ga:revenuePerTransaction'],
            ]);
        $generalReportRequest = clone $reportRequest;
        $generalReportRequest->setMetrics($generalReportMetrics);

        //top products
        $topProductsReportMetrics = $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:itemQuantity'],
                ['expression' => 'ga:itemRevenue'],
            ]);

        $productNameDimension = $this->googleAnalyticsService->buildDimensionByName('ga:productName');

        $topProductsReportRequest = clone $reportRequest;
        $topProductsReportRequest->setMetrics($topProductsReportMetrics);
        $topProductsReportRequest->setDimensions([$productNameDimension]);

        // top revenue generating source
        $topSourcesByRevenueReportMetrics =  $this->googleAnalyticsService->buildMetrics(
            [
                ['expression' => 'ga:sessions'],
                ['expression' => 'ga:users'],
                ['expression' => 'ga:transactions'],
                ['expression' => 'ga:revenuePerTransaction'],
                ['expression' => 'ga:transactionsPerSession'],
                ['expression' => 'ga:transactionRevenuePerSession'],
                ['expression' => 'ga:itemRevenue'],
            ]);
        $sourceDimension = $this->googleAnalyticsService->buildDimensionByName('ga:source');

        $itemRevenueDescOrder = new \Google_Service_AnalyticsReporting_OrderBy();
        $itemRevenueDescOrder->setFieldName('ga:itemRevenue');
        $itemRevenueDescOrder->setSortOrder('DESCENDING');

        $topSourcesByRevenueReportRequest = clone $reportRequest;
        $topSourcesByRevenueReportRequest->setMetrics($topSourcesByRevenueReportMetrics);
        $topSourcesByRevenueReportRequest->setDimensions([$sourceDimension]);
        $topSourcesByRevenueReportRequest->setPageSize(10);
        $topSourcesByRevenueReportRequest->setOrderBys($itemRevenueDescOrder);

        // revenue by day
        $itemRevenueMetric = $this->googleAnalyticsService->buildMetric(['expression' => 'ga:itemRevenue']);
        $dateDimension = $this->googleAnalyticsService->buildDimensionByName('ga:date');

        $revenueByDayReportRequest = clone $reportRequest;
        $revenueByDayReportRequest->setMetrics([$itemRevenueMetric]);
        $revenueByDayReportRequest->setDimensions([$dateDimension]);
        $revenueByDayReportRequest->setIncludeEmptyRows(true);

        // top countries by transactions

        $transactionsMetric = $this->googleAnalyticsService->buildMetric(['expression' => 'ga:transactions']);
        $countryDimension = $this->googleAnalyticsService->buildDimensionByName('ga:country');
        $countryISODimension = $this->googleAnalyticsService->buildDimensionByName('ga:countryIsoCode');


        $countriesByTransactionsReportRequest = clone $reportRequest;
        $countriesByTransactionsReportRequest->setMetrics([$transactionsMetric]);
        $countriesByTransactionsReportRequest->setDimensions([$countryDimension,$countryISODimension]);

        // age_genders_devices
        $usersMetric = $this->googleAnalyticsService->buildMetric(['expression' => 'ga:users']);
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

        //fetching data 
        $reportGroup = (new Batchman($analyticsReporting))
                ->setReportRequestGroup([
                    'general' => $generalReportRequest,
                    'top_products' => $topProductsReportRequest,
                    'top_sources_by_revenue' => $topSourcesByRevenueReportRequest,
                    'revenue_by_day' => $revenueByDayReportRequest,
                    'countries_by_transactions' => $countriesByTransactionsReportRequest,
                    'users_by_age' => $usersByAgeReportRequest,
                    'users_by_gender' => $usersByGenderReportRequest,
                    'users_by_platform' => $usersByPlatformReportRequest,
                ])
                ->getAll();

        $parsedReports = $this->googleAnalyticsService->parseReportGroup($reportGroup);

        
        $generalData = $parsedReports['general'];
        
        $topProducts = $this->googleAnalyticsService->getReportRows(
            $parsedReports['top_products'],
            [
                'ga:productName' => 'product',
                'ga:itemQuantity' => 'sale', 
                'ga:itemRevenue' => 'revenue',
            ],
            function ($entry) {
                $entry['sessions'] = null;
                $entry['conversion_rate'] = null;
                return $entry;
            }
        );

        $topSourcesByRevenue = $this->googleAnalyticsService->getReportRows(
            $parsedReports['top_sources_by_revenue'],
            [
                'ga:sessions' => 'sessions',
                'ga:users' => 'users', 
                'ga:transactions' => 'transactions',
                'ga:revenuePerTransaction' => 'avg_order_value',
                'ga:transactionsPerSession' => 'ecommerce_conversion_rate',
                'ga:transactionRevenuePerSession' => 'per_session_value',
                'ga:itemRevenue' => 'revenue',
                'ga:source' => 'source'
            ],function ($entry) {
                $entry['avg_order_value'] = number_format($entry['avg_order_value'], 2, '.', '');
                $entry['ecommerce_conversion_rate'] = number_format($entry['ecommerce_conversion_rate'], 2, '.', '');
                $entry['per_session_value'] = number_format($entry['per_session_value'], 2, '.', '');
                return $entry;
            });

        $revenueByDay = $this->googleAnalyticsService->getReportRows(
            $parsedReports['revenue_by_day'],
            [
                'ga:itemRevenue' => 'revenue',
                'ga:date' => 'date'
            ],function ($entry) {
                $entry['date'] = date('Y-m-d',strtotime($entry['date']));
                return $entry;
            });

        $countriesByTransactions = $this->googleAnalyticsService->getReportRows(
            $parsedReports['countries_by_transactions'],
            [
                'ga:country' => 'country',
                'ga:transactions' => 'transactions',
                'ga:countryIsoCode' => 'country_code'
            ]);

        $usersByAge = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_age'],['ga:userAgeBracket' => 'age', 'ga:users' => 'users']);

        $usersByGender = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_gender'],['ga:userGender' => 'gender', 'ga:users' => 'users']);

        $usersByPlatform = $this->googleAnalyticsService->getReportRows(
            $parsedReports['users_by_platform'],['ga:operatingSystem' => 'platform', 'ga:users' => 'users']);

        

        $this->data = [
            'clicks' => $totalClicks,
            'revenue' => $generalData['total']['ga:itemRevenue'],
            'spend' => $totalSpend,
            'ctr' => $totalCTR,
            'transactions' => $generalData['total']['ga:transactions'],
            'avg_order_value' => $generalData['total']['ga:revenuePerTransaction'],
            'top_products' => $topProducts,
            'top_revenue_generating_source' => $topSourcesByRevenue,
            'daily_revenue'=> $revenueByDay,
            'top_countries_by_transactions' => $countriesByTransactions,
            'age_genders_devices' => [
                'age' => $usersByAge,
                'genders' => $usersByGender,
                'devices' => $usersByPlatform
            ],
            'roi_by_source' => []
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

        if ($this->data['avg_order_value']) {
            $emailData['avg_order_value'] = number_format($this->data['avg_order_value'], 2, '.', '');
        }

        if ($this->data['daily_revenue']) {
            $chartData = array_map(function ($item) {
                    return [
                    'x' => $item['date'],
                    'y' => $item['revenue']
                    ];
                },$this->data['daily_revenue']);
            $dailyRevenueChartUrl = $this->chartService->getLineTimeseriesChartImageUrl($chartData,
                    [
                    'title' => 'Daily Revenue',
                    'label-name' => 'revenue',
                    'line-color' => '#1976d2',
                    'line-area-color' => 'transparent'
                    ]);
            $emailData['daily_revenue_chart_url'] = $dailyRevenueChartUrl;
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

        if ($this->data['top_countries_by_transactions']) {
            $countries = new \PragmaRX\Countries\Package\Countries;
            $mapData =  array_reduce($this->data['top_countries_by_transactions'],function ($result,$item) use($countries) {
                $country = $countries->where('cca2',$item['country_code'])->first();
                if ($country->isNotEmpty()) {
                    $result[$country->cca3] = $item['transactions']; 
                }
                return $result;
            },[]);
            $url = $this->chartService->getMapChartImageUrl($mapData);
            $emailData['top_countries_by_transactions_chart_url'] = $url;
        }


        return $emailData;
    }

    public function invalidAccountsException()
    {
        return new InvalidArgumentException(implode(', ',$this->requiredAccountTypes)
            .' accounts are required for generating report');
    }
}

?>