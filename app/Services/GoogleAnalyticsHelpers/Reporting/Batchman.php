<?php

namespace App\Services\GoogleAnalyticsHelpers\Reporting;
 
class Batchman
{
    private $reportNames = [];
    private $reportRequests = [];
    private $reportResults = [];

    public function __construct(\Google_Service_AnalyticsReporting $analyticsReporting) {
        $this->analyticsReporting = $analyticsReporting;
    }

    public function setReportRequestGroup($requestGroup)
    {
        $this->reportNames = array_keys($requestGroup);
        $this->reportRequests = array_values($requestGroup);
        return $this;
    }

    public function getAll()
    {
        $chunkedReportRequests = array_chunk($this->reportRequests,5);
        foreach ($chunkedReportRequests as $requests) {
            $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
            $body->setReportRequests($requests);
            $result = $this->analyticsReporting->reports->batchGet($body);
            $this->reportResults = array_merge($this->reportResults,$result->reports);
        }
        return array_combine($this->reportNames,$this->reportResults);
    }
}

