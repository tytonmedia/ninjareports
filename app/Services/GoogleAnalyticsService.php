<?php


namespace App\Services;
 
class GoogleAnalyticsService
{
    private $analyticsReporting;
    public function __construct() {
        
    }

    public function initAnalytics($accessToken)
    {
        $configFilePath = main_path('google.json');
        $client = new \Google_Client();
        $client->setAuthConfig($configFilePath);
        $client->addScope([\Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Analytics::ANALYTICS_READONLY]);
        $client->setAccessToken($accessToken);
        return  new \Google_Service_Analytics($client);
    }

    public function initAnalyticsReporting($accessToken)
    {
        $configFilePath = main_path('google.json');
        $client = new \Google_Client();
        $client->setAuthConfig($configFilePath);
        $client->addScope([\Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Analytics::ANALYTICS_READONLY]);
        $client->setAccessToken($accessToken);
        return new \Google_Service_AnalyticsReporting($client);
    }

    public function buildMetric($data)
    {
        $metric = new \Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression($data['expression']);
        if (array_key_exists('alias',$data)) {
            $metric->setAlias($data['alias']);
        }
        if (array_key_exists('formatting-type',$data)) {
            $metric->setFormattingType($data['formatting-type']);
        }
        return $metric;
    }

    public function buildMetrics($entries)
    {
        $metrics = [];
        foreach ($entries as $entry) {
            $metrics[] = $this->buildMetric($entry);
        }
        return $metrics;
    }

    public function buildDimension($data)
    {
       $dimension = new \Google_Service_AnalyticsReporting_Dimension();
       $dimension->setName($data['name']);
       return $dimension;
    }

    public function buildDimensionByName($name)
    {
       $dimension = new \Google_Service_AnalyticsReporting_Dimension();
       $dimension->setName($name);
       return $dimension;
    }

    public function buildDimensions($entries)
    {
        $dimensions = [];
        foreach ($entries as $entry) {
            $dimensions[] = $this->buildDimension($entry);
        }
        return $dimensions;
    }

    public function parseReport(\Google_Service_AnalyticsReporting_Report $report)
    {
        $parsedData = [
            'rows' => [],
            'total' => [],
        ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaderEntries = $header->getMetricHeader()->getMetricHeaderEntries();
        $metricHeaders = array_map(function ($headerEntry) {
                return $headerEntry['name'];
            },$metricHeaderEntries);
        $reportData = $report->getData();
        $rows = $reportData->getRows();
        $totals = $reportData->getTotals();
        foreach ($rows as $row) {
            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics();
            $entryDimesions = is_array($dimensionHeaders)? array_combine($dimensionHeaders,$dimensions):[];
            $entryMetrics = array_combine($metricHeaders,$metrics[0]->getValues());
            $entry = [
                'metrics' => $entryMetrics,
                'dimensions' => $entryDimesions,
                'merged_value' => array_merge($entryMetrics,$entryDimesions),
            ];
            $parsedData['rows'][] = $entry;
        }
        $parsedData['total'] = array_combine($metricHeaders,$totals[0]->getValues());
        return $parsedData;
    }

    public function parseGaData(\Google_Service_Analytics_GaData $data)
    {
        $parsedData = [
            'rows' => [],
            'total' => [],
        ];
        $rowHeaders = array_map(function ($columnHeader) {
                return $columnHeader->name;
            },$data->columnHeaders);
        
        foreach ($data->rows as $row) {
            $entry = [
                'metrics' => null,
                'dimensions' => null,
                'merged_value' => array_combine($rowHeaders,$row),
            ];
            $parsedData['rows'][] = $entry;
        }
        $parsedData['total'] = $data->totalsForAllResults;
        return $parsedData;
    }

    public function parseReports($reports)
    {
        $parsedReports = [];
        foreach ($reports as $report) {
            $parsedReports[] = $this->parseReport($report);
        }
        return $parsedReports;
    }

    public function parseReportGroup($reportGroup)
    {
        $parsedReports = [];
        foreach ($reportGroup as $name => $report) {
            $parsedReports[$name] = $this->parseReport($report);
        }
        return $parsedReports;
    }

    public function getReportRows($report,$aliases=[],$alterEntry=null)
    {
        $rows = [];
        foreach ($report['rows'] as $row) {
            $row = $row['merged_value'];
            $entry = [];
            foreach ($row as $key => $value) {
                $entryKey = array_key_exists($key,$aliases)? $aliases[$key] : $key;
                $entry[$entryKey] = $value;
            }
            if (is_callable($alterEntry)) {
                $entry = call_user_func_array($alterEntry,[$entry,$row,$report]);
            }
            $rows[] = $entry;
        }
        return $rows;
    }

    public function getReportRowsWithPercentage($report,$aliases,$percenatgeFeild)
    {
        return $this->getReportRows($report,$aliases,
            function ($entry,$row,$report) use($percenatgeFeild) {
                $entry['percentage'] = $row[$percenatgeFeild]/$report['total']['ga:users'] * 100;
                return $entry;
            });
    }
}