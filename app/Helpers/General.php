<?php
if (!function_exists('pr')) {

    function pr($e)
    {
        echo '<pre>';
        print_r($e);
        echo '</pre>';
    }

}

if (!function_exists('vd')) {

    function vd()
    {
        foreach (func_get_args() as $e) {
            echo "<pre>";
            var_dump($e);
            echo "</pre>";
        }
    }

}

if (!function_exists('jl')) {

    function jl($e, $loc = __DIR__, $file_name = '', $raw_log = false)
    {
        $raw_log = $raw_log === true;
        if (!is_dir($loc)) {
            $loc = __DIR__;
        }

        if (!$file_name) {
            $file_name = 'log' . (!$raw_log ? '.json' : '');
        }
        $log_data = $raw_log ? print_r($e, true) : @json_encode($e, JSON_PRETTY_PRINT);
        @error_log($log_data . "\n\n", 3, $loc . "/{$file_name}");
    }

}

if (!function_exists('lg')) {

    function lg($e, $loc = __DIR__, $file_name = '')
    {
        jl($e, $loc, $file_name, true);
    }

}

if (!function_exists('jc')) {

    function jc($data, $loc = __DIR__, $file_name = 'log.json')
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($loc . "/{$file_name}", $json);
    }

}

if (!function_exists('richtextdata')) {

    function richtextdata($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

}

if (!function_exists('main_path')) {

    /**
     * Get the path to the main folder.
     *
     * @param  string  $path
     * @return string
     */
    function main_path($path = '')
    {
        return dirname(app()->make('path')) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

}

if (!function_exists('validateTokens')) {
    function validateTokens()
    {
        $accounts = \App\Models\Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        $fb_access_token = \Session::get('fb_access_token');
        $ga_access_token = \Session::get('ga_access_token');
        foreach ($accounts as $account) {

            // Check Facebook Token Validation
            if (!$fb_access_token) {
                if ($account->type == 'facebook') {
                    $fb = fb_connect();
                    try {
                        $fb->get(
                            '/debug_token?input_token=' . $account->token,
                            $account->token
                        );
                        $fb_token_status = true;
                        \Session::put('fb_access_token', 1);
                    } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                        $fb_token_status = false;
                    } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                        $fb_token_status = false;
                    }
                    if (!$fb_token_status) {
                        \App\Models\Account::where('user_id', auth()->user()->id)->where('type', 'facebook')->update(['status' => 0]);
                    }
                }
            }
        }
    }

}

if (!function_exists('fb_connect')) {
    function fb_connect()
    {
        $fb = new \Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_SECRET'),
            'default_graph_version' => 'v2.11',
            'persistent_data_handler' => new \App\Libraries\Facebook\FacebookPersistentDataHandler(),
        ]);
        return $fb;
    }
}

if (!function_exists('fb_token')) {
    function fb_token()
    {
        $token = \App\Models\Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->pluck('token')->first();
        return $token;
    }
}

if (!function_exists('analytics_token')) {
    function analytics_token()
    {
        $token = \App\Models\Account::where('type', 'analytics')->where('user_id', auth()->user()->id)->pluck('token')->first();
        $token = (array) json_decode($token);
        $client = analytics_connect();
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($token);
            $access_token = $client->getAccessToken();
            \App\Models\Account::where('type', 'analytics')->where('user_id', auth()->user()->id)->update([
                'token' => json_encode($access_token),
            ]);
        }
        $token = \App\Models\Account::where('type', 'analytics')->where('user_id', auth()->user()->id)->pluck('token')->first();
        if ($token) {
            return (array) json_decode($token);
        }
        return false;
    }
}

if (!function_exists('analytics_connect')) {
    function analytics_connect()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(main_path('google.json'));
        $client->addScope([\Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Analytics::ANALYTICS_READONLY]);
        $client->setRedirectUri(route('connect.analytics.callback'));
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        $client->setApprovalPrompt('force');
        return $client;
    }
}

if (!function_exists('adwords_connect')) {
    function adwords_connect()
    {
        $oauth2 = new \Google\Auth\OAuth2([
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri' => 'https://www.googleapis.com/oauth2/v4/token',
            'redirectUri' => env('GOOGLE_ADWORDS_REDIRECT_URL'),
            'clientId' => env('GOOGLE_ADWORDS_CLIENT_ID'),
            'clientSecret' => env('GOOGLE_ADWORDS_CLIENT_SECRET'),
            'scope' => [\Google_Service_Oauth2::USERINFO_EMAIL, 'https://www.googleapis.com/auth/adwords'],
        ]);
        return $oauth2;
    }
}

if (!function_exists('adwords_token')) {
    function adwords_token()
    {
        $token = \App\Models\Account::where('type', 'adword')->where('user_id', auth()->user()->id)->pluck('token')->first();
        return $token;
    }
}

if (!function_exists('adwords_session')) {
    function adwords_session($customerId)
    {
        $oauth2Token = (new \Google\AdsApi\Common\OAuth2TokenBuilder())
            ->withClientId(env('GOOGLE_ADWORDS_CLIENT_ID'))
            ->withClientSecret(env('GOOGLE_ADWORDS_CLIENT_SECRET'))
            ->withRefreshToken(adwords_token())
            ->build();
        $soapSettings = (new \Google\AdsApi\Common\SoapSettingsBuilder())
            ->disableSslVerify()
            ->build();
        $session = (new \Google\AdsApi\AdWords\AdWordsSessionBuilder())
            ->withOAuth2Credential($oauth2Token)
            ->withSoapSettings($soapSettings)
            ->withClientCustomerId($customerId)
            ->withDeveloperToken(env('ADWORDS_TOKEN'))
            ->build();
        return $session;
    }
}
