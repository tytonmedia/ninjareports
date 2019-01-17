<?php

namespace App\Services\Report\TemplateData;
 
class FacebookAdsReportData
{
    private $requiredAccountTypes = ['google_analytics','facebook'];
    private $accounts = null;
    private $data = null;

    public function __construct() { 
          
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

        // $analyticsAccessToken = $this->accounts['google_analytics']['access_token'];

        $this->data = [
            'spend' => null,
            'impressions' => null,
            'ctr' => null,
            'clicks' => null,
            'avg_cpc' => null,
            'frequency' => null,
            'spend_by_day' => null,
            'age_genders_devices' => null,
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


