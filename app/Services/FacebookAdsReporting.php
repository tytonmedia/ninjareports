<?php 

namespace App\Services;

use InvalidArgumentException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Cursor;

 
class FacebookAdsReporting 
{
    private $appId;
    private $appSecret;
    private $accessToken;
    private $accountId;

    private $reportConfig = [
        'from_date' => null,
        'to_date' => null
    ];

    public function __construct($appId,$appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function init($accessToken,$accountId)
    {
        \FacebookAds\Api::init(
            $this->appId, 
            $this->appSecret,
            $accessToken
        );
        $this->accessToken = $accessToken;
        $this->accountId = $accountId;
        return $this;
    }

    public function betweenDates($from,$to)
    {
        $this->reportConfig['from_date'] = $from;
        $this->reportConfig['to_date'] = $to;
        return $this;
    }

    public function validateReportConfig()
    {
        if (!array_get($this->reportConfig,'from_date') || !array_get($this->reportConfig,'to_date')) {
            throw new InvalidArgumentException('Invalid report config : from_date and to_date are required');
        }
    }

    public function getAccountInstance()
    {
        return new AdAccount('act_'.$this->accountId);
    }

    public function getCampaignInstance()
    {
        return new Campaign('act_'.$this->accountId);
    }

    public function getResultData(Cursor $cursor)
    {
        $response = $cursor->getResponse();
        if ($response) {
            return $response->getContent();
        }
    }

    public function accountOverview()
    {
        $this->validateReportConfig();

        $account = $this->getAccountInstance();

        $result = $account->getInsights(
          ['spend','impressions','ctr','clicks','cpc','frequency'],
          ['time_range' => ['since' => $this->reportConfig['from_date'],'until' => $this->reportConfig['to_date']]]
        );
        return $this->getResultData($result);
    }

    public function spendByDay()
    {
        $this->validateReportConfig();

        $account = $this->getAccountInstance();

        $result = $account->getInsights(
            ['spend'],
            [
                'time_range' => [
                    'since' => $this->reportConfig['from_date'],
                    'until' => $this->reportConfig['to_date']
                ],
                'time_increment' => 1
            ]);
        return $this->getResultData($result);
    }

    public function clicksByDay()
    {
        $this->validateReportConfig();

        $account = $this->getAccountInstance();

        $result = $account->getInsights(
            ['clicks'],
            [
                'time_range' => [
                    'since' => $this->reportConfig['from_date'],
                    'until' => $this->reportConfig['to_date']
                ],
                'time_increment' => 1
            ]);
        return $this->getResultData($result);
    }

    public function demographics()
    {
        $this->validateReportConfig();

        $account = $this->getAccountInstance();

        $timeRange = [
            'since' => $this->reportConfig['from_date'],
            'until' => $this->reportConfig['to_date']
        ];

        $ageResult = $account->getInsights(
            ['impressions'],
            [
                'time_range' => $timeRange,
                'breakdowns' => ['age']
            ]);
        $genderResult = $account->getInsights(
            ['impressions'],
            [
                'time_range' => $timeRange,
                'breakdowns' => ['gender']
            ]);
        $deviceResult = $account->getInsights(
            ['impressions'],
            [
                'time_range' => $timeRange,
                'breakdowns' => ['impression_device']
            ]);

        return [
            'age' => $this->getResultData($ageResult),
            'genders' => $this->getResultData($genderResult),
            'devices' => $this->getResultData($deviceResult),
        ];
    }

    public function topPerformingCampaigns()
    {
        $this->validateReportConfig();

        $campaign = $this->getCampaignInstance();

        $result = $campaign->getInsights(
            ['campaign_name','impressions','reach','frequency','clicks','ctr','spend'],
            [
                'time_range' => [
                    'since' => $this->reportConfig['from_date'],
                    'until' => $this->reportConfig['to_date']
                ],
                'level' => 'campaign',
                'sort' => ['ctr_descending']
            ]
        );
        return $this->getResultData($result);
    }

    public function topPerformingCountries()
    {
        $this->validateReportConfig();

        $account = $this->getAccountInstance();

        $result = $account->getInsights(
            ['clicks'],
            [
                'time_range' => [
                    'since' => $this->reportConfig['from_date'],
                    'until' => $this->reportConfig['to_date']
                ],
                'breakdowns' => ['country'],
                'sort' => ['clicks_descending']
            ]);
        
        return $this->getResultData($result);
    }

}

