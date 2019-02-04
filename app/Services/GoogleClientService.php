<?php


namespace App\Services;
 
class GoogleClientService
{
    private $client = null;

    public function __construct() {
        
    }

    public function init($config)
    {
        $client = new \Google_Client($config);
        $this->client = $client;
        return $this;
    }
    
    public function initUsingConfig($configFilePath)
    {
        $client = new \Google_Client();
        $client->setAuthConfig($configFilePath);
        // $client->addScope([\Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Analytics::ANALYTICS_READONLY]);
        // $client->setAccessType("offline");        // offline access
        // $client->setIncludeGrantedScopes(true);   // incremental auth
        $this->client = $client;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function refreshAccessTokenIfExpired($accessToken)
    {
        if (!$this->client) {
           return;
        }
        $this->client->setAccessToken($accessToken);
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken();
            return $this->client->getAccessToken();
        }
        return $accessToken;
    }

    public function reviseAccessToken($accessToken)
    {
        $result = [
            'revisionAction' => 'none',
            'accessToken' => $accessToken
        ];

        if (!$this->client) {
            return;
        }

        $this->client->setAccessToken($accessToken);
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken();
            $result['revisionAction'] = 'refresh';
            $result['accessToken'] = $this->client->getAccessToken();
        }

        return (object) $result;
    }
}

