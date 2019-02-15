<?php

namespace App\Services\Report;
 
class ReportSenderResult
{
    public $receivers = [];
    public $totalSentCount = null;
    public function __construct() {
        
    }
    

    /**
     * Get the value of receivers
     */ 
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Set the value of receivers
     *
     * @return  self
     */ 
    public function setReceivers($receivers)
    {
        $this->receivers = $receivers;

        return $this;
    }
}

