<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Facades\Input;
use Session;

class ConnectController extends Controller
{
    public function test()
    {
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->first();
    }
    public function facebook()
    {
        $fb = fb_connect();
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['email', 'ads_read']; // Optional permissions
        $loginUrl = $helper->getLoginUrl(route('connect.facebook.callback'), $permissions);
        return redirect($loginUrl);
    }

    public function facebookCallback()
    {
        $fb = fb_connect();
        $helper = $fb->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Session::flash('alert-danger', $e->getMessage());
            return redirect()->route('accounts.index');
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            Session::flash('alert-danger', $e->getMessage());
            return redirect()->route('accounts.index');
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                Session::flash('alert-danger', 'Bad Request.');
                return redirect()->route('accounts.index');
            }
            exit;
        }
        $oAuth2Client = $fb->getOAuth2Client();
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        $tokenMetadata->validateAppId(env('FACEBOOK_APP_ID'));
        $tokenMetadata->validateExpiration();

        if (!$accessToken->isLongLived()) {
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                Session::flash('alert-danger', 'Error getting long-lived access token: ' . $e->getMessage());
                return redirect()->route('accounts.index');
            }
        }
        try {
            $user = $fb->get('/me?fields=email', (string) $accessToken);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Session::flash('alert-danger', 'Graph returned an error: ' . $e->getMessage());
            return redirect()->route('accounts.index');
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            Session::flash('alert-danger', 'Facebook SDK returned an error: ' . $e->getMessage());
            return redirect()->route('accounts.index');
        }
        $me = $user->getGraphUser();
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->first();
        $account_update_array = [
            'user_id' => auth()->user()->id,
            'type' => 'facebook',
            'title' => 'Facebook Ads',
            'email' => $me->getEmail(),
            'status' => 1,
            'token' => (string) $accessToken,
        ];
        if ($account) {
            Account::where('id', $account->id)->update($account_update_array);
        } else {
            Account::create($account_update_array);
        }
        return redirect()->route('accounts.index');
    }

    public function analytics()
    {

        $client = $this->ga();
        $client->setRedirectUri('https://localhost/nr/public/connect/google/callback');
        return redirect($client->createAuthUrl());
    }

    public function analyticsCallback()
    {
        $client = $this->ga();
        $code = Input::get('code');
        $client->setRedirectUri('https://localhost/nr/public/connect/google/callback');
        $token = $client->fetchAccessTokenWithAuthCode($code);
        Session::put('google_access_token', $token);
        return redirect()->route('connect.test');
    }

    public function ganalytics()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(main_path('google.json'));
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        return $client;
    }
}
