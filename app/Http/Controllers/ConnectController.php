<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Facades\Input;
use Session;

class ConnectController extends Controller
{
    public function test()
    {
        $client = analytics_connect();
        $client->setAccessToken(analytics_token());
        $analytics = new \Google_Service_Analytics($client);
        $accounts = $analytics->management_accounts->listManagementAccounts()->getItems();
        if ($accounts) {
            foreach ($accounts as $account) {
                pr($account->getId());
                pr($account->getName());
            }
        }
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
        Session::put('fb_access_token', 1);
        $me = $user->getGraphUser();
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->first();
        $account_update_array = [
            'user_id' => auth()->user()->id,
            'type' => 'facebook',
            'title' => 'Facebook Ads',
            'email' => $me->getEmail(),
            'status' => 1,
            'is_active' => 1,
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

        $client = analytics_connect();
        return redirect($client->createAuthUrl());
    }

    public function analyticsCallback()
    {
        $client = analytics_connect();
        $code = Input::get('code');
        $client->authenticate($code);
        $token = $client->getAccessToken();
        $client->setAccessToken($token);
        $user = new \Google_Service_Oauth2($client);
        Session::put('ga_access_token', 1);
        $account = Account::where('type', 'analytics')->where('user_id', auth()->user()->id)->first();
        $account_update_array = [
            'user_id' => auth()->user()->id,
            'type' => 'analytics',
            'title' => 'Google Analytics',
            'email' => $user->userinfo->get()->email,
            'status' => 1,
            'is_active' => 1,
            'token' => json_encode($token),
        ];
        if ($account) {
            Account::where('id', $account->id)->update($account_update_array);
        } else {
            Account::create($account_update_array);
        }
        return redirect()->route('accounts.index');
    }

    public function adwords()
    {
        $oauth2 = adwords_connect();
        $oauth2->setState(sha1(openssl_random_pseudo_bytes(1024)));
        Session::put('adwords_state', $oauth2->getState());
        $config = [
            'access_type' => 'offline',
        ];
        return redirect($oauth2->buildFullAuthorizationUri($config));
    }

    public function adwordsCallback()
    {
        $state = Input::get('state');
        if ($state == Session::get('adwords_state')) {
            $code = Input::get('code');
            $oauth2 = adwords_connect();
            $oauth2->setCode($code);
            $authToken = $oauth2->fetchAuthToken();
            $user_result = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $authToken['access_token']);
            $user = json_decode($user_result);
            $refresh_token = isset($authToken['refresh_token']) ? $authToken['refresh_token'] : '';
            Session::put('gadwords_access_token', 1);
            $account = Account::where('type', 'adword')->where('user_id', auth()->user()->id)->first();
            $account_update_array = [
                'user_id' => auth()->user()->id,
                'type' => 'adword',
                'title' => 'Google Adwords',
                'email' => $user->email,
                'status' => 1,
                'is_active' => 1,
            ];
            if ($refresh_token) {
                $account_update_array['token'] = $refresh_token;
            }
            if ($account) {
                Account::where('id', $account->id)->update($account_update_array);
            } else {
                Account::create($account_update_array);
            }
            return redirect()->route('accounts.index');
        } else {
            Session::flash('alert-danger', 'Invalid State.');
            return redirect()->route('accounts.index');
        }
    }

}
