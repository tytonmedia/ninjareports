<?php 

namespace App\Services\Report\TemplateData;

use App\Services\GoogleAdwordsReporting;

 
class GoogleAdsReportData
{
    private $requiredAccountTypes = ['google_analytics','google_adwords'];
    private $accounts = null;
    private $data = null;

    public function __construct(
        GoogleAdwordsReporting $googleAdwordsReporting
    ) {
        $this->googleAdwordsReporting = $googleAdwordsReporting;
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
            return;
        }
        $this->accounts = array_only($accounts,$this->requiredAccountTypes);
        return $this;
    }

    public function generate($reportDate,$fromDate,$toDate)
    {
        if (!array_has($this->accounts,$this->requiredAccountTypes)) {
            return;
        }

        /**
         * Google adwords data fetching
         */
        $adwordsAccessToken = $this->accounts['google_adwords']['access_token'];
        $adwordsCustomerId = $this->accounts['google_adwords']['client_customer_id'];
        $adwordsReporting = $this->googleAdwordsReporting->initSession($adwordsAccessToken,$adwordsCustomerId);

        $adwordsGeneralData = $adwordsReporting->getGeneralReport($fromDate,$toDate);
        /**
         * Google analytics data fetching
         */


        $this->data = [
            'spend' => null,
            'impressions' => null,
            'ctr' => null,
            'clicks' => null,
            'avg_cpc' => null,
            'conversions' => null,
            'spend_by_day' => null,
            'conversions_by_day' => null,
            'age_genders_devices' => null,
            'top_keywords' => null,
            'top_performing_campaigns' => null,
            'performance_by_country'=> null
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
    }
    
}

