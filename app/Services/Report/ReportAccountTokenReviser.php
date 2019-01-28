<?php


namespace App\Services\Report;

use App\Services\GoogleClientService;

 
class ReportAccountTokenReviser
{
    public function __construct(GoogleClientService $googleClientService) {
        $this->googleClientService = $googleClientService;
    }
    
    public function revise($report)
    {
        $reportAccounts = $report->accounts;

        $reportAccountsByType = $reportAccounts->keyBy('ad_account.account.type');

        if ($analyticsAccount = $reportAccountsByType->get('analytics')) {
            $accessToken = json_decode($analyticsAccount->ad_account->account->token,true);
            $revisionResult = $this->googleClientService->init(main_path('google.json'))
                                ->reviseAccessToken($accessToken);
            if ($revisionResult->revisionAction == 'refresh') {
                $analyticsAccount->ad_account->account->token = json_encode($revisionResult->accessToken);
                $analyticsAccount->ad_account->account->save();
            }
        }

        if ($googleSearchAccount = $reportAccountsByType->get('google-search')) {
            $accessToken = json_decode($googleSearchAccount->ad_account->account->token,true);
            $revisionResult = $this->googleClientService->init(main_path('google-search.json'))
                                ->reviseAccessToken($accessToken);
            if ($revisionResult->revisionAction == 'refresh') {
                $googleSearchAccount->ad_account->account->token = json_encode($revisionResult->accessToken);
                $googleSearchAccount->ad_account->account->save();
            }
        }

        if ($googleAdwordsAccount = $reportAccountsByType->get('adword')) {
            $token = $googleAdwordsAccount->ad_account->account->token;
            
        }
        return $report;
    }
}

