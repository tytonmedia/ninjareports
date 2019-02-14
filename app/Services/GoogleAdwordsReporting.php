<?php

namespace App\Services;

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Common\SoapSettingsBuilder;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\AdWordsServices;

use Google\AdsApi\AdWords\Query\v201806\ReportQueryBuilder;
use Google\AdsApi\AdWords\Reporting\v201806\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201806\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201806\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettings;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201806\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201806\cm\LocationCriterionService;
use Google\AdsApi\AdWords\Query\v201806\ServiceQueryBuilder;

 
class GoogleAdwordsReporting
{
    private $adwordsSession = null;
    public function __construct() {
        
    }
 
    public function getCampaignReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'CampaignId',
                'CampaignName',
                'CampaignStatus',
                'Impressions',
                'Clicks',
                'Cost',
                'Ctr',
                'AverageCpc',
                'AllConversions'
            ])
            ->from(ReportDefinitionReportType::CAMPAIGN_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($query);
        $reportCSVString = $reportResult->getAsString();
        $reportData = $this->parseCSVReport($reportCSVString);

        $formatedReportData = $this->formatParsedData($reportData,[
            'Cost' => [$this,'costFormatter'],
            'Avg. CPC' => [$this,'costFormatter']
        ]); 
        
        return $formatedReportData;
    }

    public function getCampaignSpendByDayReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'Date',
                'Cost'
            ])
            ->from(ReportDefinitionReportType::CAMPAIGN_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($query);
        $reportCSVString = $reportResult->getAsString();
        $reportData = $this->parseCSVReport($reportCSVString);

        $reportData['rows'] = $this->array_merge_duplicates(
            $reportData['rows'],
            'Day',
            function ($currentItem,$duplicateItem) {
                $currentItem['Cost'] =  (int) $currentItem['Cost'] + (int) $duplicateItem['Cost'];
                return $currentItem;
            }
        );

        $formatedReportData = $this->formatParsedData($reportData,[
            'Cost' => [$this,'costFormatter']
        ]);

        return $formatedReportData;
    }

    public function getCampaignConversionsByDayReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'Date',
                'AllConversions'
            ])
            ->from(ReportDefinitionReportType::CAMPAIGN_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportSettings = (new ReportSettingsBuilder())
                ->includeZeroImpressions(true)
                ->build();

        $reportResult = $this->runAwql($query,$reportSettings);
        $reportCSVString = $reportResult->getAsString();
        $reportData = $this->parseCSVReport($reportCSVString);
        
        $reportData['rows'] = $this->array_merge_duplicates(
            $reportData['rows'],
            'Day',
            function ($currentItem,$duplicateItem) {
                $currentItem['All conv.'] =  (int) $currentItem['All conv.'] + (int) $duplicateItem['All conv.'];
                return $currentItem;
            }
        );

        return $reportData;
    }
    public function getCampaignClicksByDayReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'Date',
                'Clicks'
            ])
            ->from(ReportDefinitionReportType::CAMPAIGN_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportSettings = (new ReportSettingsBuilder())
                ->includeZeroImpressions(true)
                ->build();

        $reportResult = $this->runAwql($query,$reportSettings);
        $reportCSVString = $reportResult->getAsString();
        $reportData = $this->parseCSVReport($reportCSVString);
        
        $reportData['rows'] = $this->array_merge_duplicates(
            $reportData['rows'],
            'Day',
            function ($currentItem,$duplicateItem) {
                $currentItem['Clicks'] =  (int) $currentItem['Clicks'] + (int) $duplicateItem['Clicks'];
                return $currentItem;
            }
        );

        return $reportData;
    }

    public function getAgeGenderDeviceReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $impressionsByAgeQuery = (new ReportQueryBuilder())
            ->select([
                'Criteria',
                'Impressions'
            ])
            ->from(ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT)
            ->where('CriteriaType')->equalTo('AGE_RANGE')
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($impressionsByAgeQuery);
        $reportCSVString = $reportResult->getAsString();
        $impressionsByAgeData = $this->parseCSVReport($reportCSVString,['Keyword / Placement' => 'AgeRange']);


        $impressionsByGenderQuery = (new ReportQueryBuilder())
            ->select([
                'Criteria',
                'Impressions'
            ])
            ->from(ReportDefinitionReportType::GENDER_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $impressionsByGenderResult = $this->runAwql($impressionsByGenderQuery);
        $impressionsByGenderData = $this->parseCSVReport($impressionsByGenderResult->getAsString());
        
        $impressionsByGender = [];
        foreach ($impressionsByGenderData['rows'] as $row) {
            $index = array_search($row['Gender'], array_column($impressionsByGender, 'Gender'));
            if ($index !== false) {
                $impressionsByGender[$index]['Impressions'] = (int) $impressionsByGender[$index]['Impressions'] + (int) $row['Impressions'];
            } else {
                $impressionsByGender[] = [
                    'Gender' => $row['Gender'],
                    'Impressions' => $row['Impressions']
                ];
            }
        }

        $impressionsByDeviceQuery = (new ReportQueryBuilder())
            ->select([
                'Device',
                'Impressions'
            ])
            ->from(ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($impressionsByDeviceQuery);
        $impressionsByDeviceData = $this->parseCSVReport($reportResult->getAsString());
        
        $impressionsByDevice = [];
        foreach ($impressionsByDeviceData['rows'] as $row) {
            $index = array_search($row['Device'], array_column($impressionsByDevice, 'Device'));
            if ($index !== false) {
                $impressionsByDevice[$index]['Impressions'] = (int) $impressionsByDevice[$index]['Impressions'] + (int) $row['Impressions'];
            } else {
                $impressionsByDevice[] = [
                    'Device' => $row['Device']?: 'Unknown',
                    'Impressions' => $row['Impressions']
                ];
            }
        }

        return [
            'age' => $impressionsByAgeData['rows'],
            'genders' => $impressionsByGender,
            'devices' => $impressionsByDevice
        ];

    }

    public function getTopKeywordsReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'Criteria',
                'Clicks',
                'Impressions',
                'Ctr',
                'AverageCpc',
                'Cost',
                'AllConversions',
                'CostPerAllConversion'
            ])
            ->from(ReportDefinitionReportType::KEYWORDS_PERFORMANCE_REPORT)
            ->where('Clicks')->greaterThan(1)
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($query);
        $reportData = $this->parseCSVReport($reportResult->getAsString());

        $formatedReportData = $this->formatParsedData($reportData,[
            'Cost' => [$this,'costFormatter'],
            'Cost / all conv.' => [$this,'costFormatter']
        ]);

        $sortedRows = collect($formatedReportData['rows'])
                        ->sortByDesc('Impressions')
                        ->values()
                        ->take(10)
                        ->all();
        return $sortedRows;
    }

    public function getTopCountriesReport($startDate,$endDate)
    {
        if (!$this->adwordsSession) {
            return;
        }
        
        $startDate = str_replace('-','',$startDate);
        $endDate = str_replace('-','',$endDate);

        $query = (new ReportQueryBuilder())
            ->select([
                'CountryCriteriaId',
                'Clicks'
            ])
            ->from(ReportDefinitionReportType::GEO_PERFORMANCE_REPORT)
            ->during($startDate,$endDate)
            ->build();

        $reportResult = $this->runAwql($query);
        $reportData = $this->parseCSVReport($reportResult->getAsString());

        $rows = collect($reportData['rows'])
                        ->sortByDesc('Clicks')
                        ->values()
                        ->take(10);

        if ($rows->isEmpty()) {
            return [];
        }

        $locationCriterionService = (new AdWordsServices)->get($this->adwordsSession,LocationCriterionService::class);
        $query = (new ServiceQueryBuilder())
                ->select(['CanonicalName','Id'])
                ->where("Id")->in($rows->pluck('Country/Territory')->all())
                ->build();

        $countries = new \PragmaRX\Countries\Package\Countries();
        $locations = $locationCriterionService->query(sprintf('%s', $query));
        $rows = $rows->map(function ($row) use($locations,$countries) {
            $location = array_first($locations,function ($location) use($row) {
                return $this->getProtectedValue($location->getLocation(),'id') == $row['Country/Territory'];
            });
            $row['CountryName'] = $location? $location->getCanonicalName() : null;
            $row['CountryISO'] = $countries->where('name.common',$row['CountryName'])->first()->cca3;
            return $row;
        });

        return $rows->all();
    }

    public function costFormatter($data)
    {
        return $data / 1000000;
    }

    public function formatParsedData($data,$formatters)
    {
        $formatedData = [
            'name' => null,
            'rows' => [],
            'total' => null
        ];

        foreach ($data['rows'] as $row) {
            foreach ($formatters as $key => $formatter) {
                if (array_key_exists($key,$row) && is_callable($formatter)) {
                    $row[$key] = call_user_func_array($formatter,[$row[$key]]);
                }
            }
            $formatedData['rows'][] = $row;
        }

        foreach ($formatters as $key => $formatter) {
            if (array_key_exists($key,$data['total']) && is_callable($formatter)) {
                $data['total'][$key] = call_user_func_array($formatter,[$data['total'][$key]]);
            }
        }

        $formatedData['total'] = $data['total'];
        
        return $formatedData;
    }


    public function parseCSVReport($csvString,$keyAliases=[])
    {
        $reportData = [
            'name' => null,
            'rows' => [],
            'total' => null
        ];
        $lines = explode("\n",$csvString);
        // dd($lines);
        $reportData['name'] = trim(array_shift($lines),'"');

        $keys = explode(',',$lines[0]);

        foreach ($keyAliases as $key => $alias) {
            $index = array_search($key,$keys);
            $keys[$index] = $alias; 
        }

        $rows = array_slice($lines,1,count($lines) - 3);

        foreach ($rows as $row) {
            $reportData['rows'][] = array_combine($keys,explode(',',$row));
        }
        $totals = explode(',',$lines[count($lines) - 2]);
        $reportData['total'] = array_combine($keys,$totals);
        return $reportData;
    }

    public function runAwql($query,ReportSettings $reportSettings=null)
    {
        $reportDownloader = new ReportDownloader($this->adwordsSession);
        if (!$reportSettings) {
            $reportSettings = (new ReportSettingsBuilder())
                ->includeZeroImpressions(false)
                ->build();
        }
        return $reportDownloader->downloadReportWithAwql(
            sprintf('%s', $query),
            DownloadFormat::CSV,
            $reportSettings
        );
    }

    public function getSession()
    {
        return $this->adwordsSession;
    }

    public function initSession($accessToken,$clientCustomerId)
    {
        $config = json_decode(file_get_contents(main_path('google.json')),true);
        $clientId = env('GOOGLE_ADWORDS_CLIENT_ID',$config['web']['client_id']) ;
        $clientSecret = env('GOOGLE_ADWORDS_CLIENT_SECRET',$config['web']['client_secret']);

        $oauth2Token = (new OAuth2TokenBuilder())
            ->withClientId($clientId)
            ->withClientSecret($clientSecret)
            ->withRefreshToken($accessToken)
            ->build();
        $soapSettings = (new SoapSettingsBuilder())
            ->disableSslVerify()
            ->build();
        $session = (new AdWordsSessionBuilder())
            ->withOAuth2Credential($oauth2Token)
            ->withSoapSettings($soapSettings)
            ->withClientCustomerId($clientCustomerId)
            ->withDeveloperToken('SjC60g7KIOJyfaVbXFHUjQ')
            ->build();
        $this->adwordsSession = $session;
        return $this;
    }

    
    public function array_merge_duplicates($array,$key,callable $mergeAction)
    {
        $result = [];
        foreach ($array as $item) {
            $index = array_search($item[$key], array_column($result, $key));
            if ($index !== false) {
                $result[$index] = call_user_func_array($mergeAction,[$item,$result[$index]]);
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    function getProtectedValue($obj,$name) {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }
}

