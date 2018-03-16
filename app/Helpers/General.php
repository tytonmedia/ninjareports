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
    function fb_token($user_id = 0)
    {
        $user_id = $user_id ? $user_id : auth()->user()->id;
        $token = \App\Models\Account::where('type', 'facebook')->where('user_id', $user_id)->pluck('token')->first();
        return $token;
    }
}

if (!function_exists('analytics_token')) {
    function analytics_token($user_id = 0)
    {
        $user_id = $user_id ? $user_id : auth()->user()->id;
        $token = \App\Models\Account::where('type', 'analytics')->where('user_id', $user_id)->pluck('token')->first();
        $token = (array) json_decode($token);
        $client = analytics_connect();
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($token);
            $access_token = $client->getAccessToken();
            \App\Models\Account::where('type', 'analytics')->where('user_id', $user_id)->update([
                'token' => json_encode($access_token),
            ]);
        }
        $token = \App\Models\Account::where('type', 'analytics')->where('user_id', $user_id)->pluck('token')->first();
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
    function adwords_token($user_id = 0)
    {
        $user_id = $user_id ? $user_id : auth()->user()->id;
        $token = \App\Models\Account::where('type', 'adword')->where('user_id', $user_id)->pluck('token')->first();
        return $token;
    }
}

if (!function_exists('adwords_session')) {
    function adwords_session($customerId, $user_id = 0)
    {
        $user_id = $user_id ? $user_id : auth()->user()->id;
        $oauth2Token = (new \Google\AdsApi\Common\OAuth2TokenBuilder())
            ->withClientId(env('GOOGLE_ADWORDS_CLIENT_ID'))
            ->withClientSecret(env('GOOGLE_ADWORDS_CLIENT_SECRET'))
            ->withRefreshToken(adwords_token($user_id))
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

if (!function_exists('make_schedules')) {
    function make_schedules($frequency, $ends_at, $user_id = 0)
    {
        if ($user_id) {
            $user_timezone = \App\User::find($user_id)->timezone;
            $user_timezone ? date_default_timezone_set($user_timezone) : '';
        } else {
            if (auth()->check()) {
                auth()->user()->timezone ? date_default_timezone_set(auth()->user()->timezone) : '';
            }
        }
        $next_send_time = '';
        $current_time = date('H:i:s');
        $current_day = date('D');
        $current_date = date('j');
        $current_month = date('n');
        if ($frequency == 'daily') {
            $frequency_time = date('H:i:s', strtotime($ends_at));
            if ($frequency_time > $current_time) {
                $next_send_time = date('Y-m-d') . ' ' . $frequency_time;
            } else {
                $next_send_time = date('Y-m-d', strtotime('+1 day', time())) . ' ' . $frequency_time;
            }
        }
        if ($frequency == 'weekly') {
            $frequency_day = $ends_at;
            $day = strtolower(date('l', strtotime($frequency_day)));
            if ($frequency_day == $current_day && '19:00:00' > $current_time) {
                $next_send_time = date('Y-m-d') . ' 19:00:00';
            } else {
                $next_send_time = date('Y-m-d', strtotime('next ' . $day)) . ' 19:00:00';
            }
        }
        if ($frequency == 'monthly') {
            $frequency_date = $ends_at;
            if ($frequency_date > $current_date) {
                $next_send_time = date('Y-m-') . sprintf('%02d', $frequency_date) . ' 19:00:00';
            } else {
                $next_send_time = date('Y-') . date('m-', strtotime('first day of +1 month')) . sprintf('%02d', $frequency_date) . ' 19:00:00';
            }
        }
        if ($frequency == 'yearly') {
            $yearly_data = explode('-', $ends_at);
            $frequency_month = $yearly_data[0];
            $frequency_date = $yearly_data[1];
            if (($frequency_month > $current_month) || ($frequency_month == $current_month && $frequency_date > $current_date) || ($frequency_month == $current_month && $frequency_date == $current_date && '19:00:00' > $current_time)) {
                $next_send_time = date('Y-') . sprintf('%02d', $frequency_month) . '-' . sprintf('%02d', $frequency_date) . ' 19:00:00';
            } else {
                $next_send_time = date('Y-m-d 19:00:00', strtotime(date('Y-') . sprintf('%02d', $frequency_month) . '-' . sprintf('%02d', $frequency_date) . ' + 1 year'));
            }
        }
        if (validateDate($next_send_time)) {
            $next_send_str_time = strtotime($next_send_time);
            date_default_timezone_set('UTC');
            return date('Y-m-d H:i:s', $next_send_str_time);
        }
        return false;
    }
}

if (!function_exists('sendMail')) {

    function sendMail($to, $subject, $template_id, $substitutions = array(), $attachments = array(), $from = 'reports@ninjareports.com', $showResponse = true)
    {
        $default_subs = [
            '%company%' => 'Ninja Reports™',
        ];

        $substitutions = count($substitutions) > 0 ? $substitutions : $default_subs;


        $response = \App\Models\SendGrid::send($to, $subject, $template_id, $substitutions, $attachments, $from);


        $encodedString = json_encode($response);

       // file_put_contents('general_response.txt', $encodedString);

        if ($showResponse) {
            return $response;
        }
        if ($response->statusCode() == 202) {
            $status=array();
            $status[]="true";
            $encodedString = json_encode($sent);

           // file_put_contents('true_sent.txt', $encodedString);
            return true;

        }
        return false;
    }

}

if (!function_exists('validateDate')) {

    function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

}

if (!function_exists('update_schedule')) {

    function update_schedule($report, $user_id)
    {
        $next_send_time = make_schedules($report->frequency, $report->ends_at, $user_id);
        $report->sent_at = date('Y-m-d H:i:s');
        $report->next_send_time = $next_send_time;
        $report->save();
    }

}

if (!function_exists('getChartUrl')) {

    function getChartUrl($json_array)
    {
        $encoded_json = '';
        if (count($json_array) > 0) {
            foreach ($json_array as $key => $data) {
                $encoded_json .= '["' . $key . '",' . $data . '],';
            }
        }
        $json = '{"options" : {"data" : {"type" : "pie","columns" : [' . $encoded_json . ']}}}';
        $raw_sig = hash_hmac('sha256', $json, env('CHARTURL_KEY'), true);
        $encoded_sig = base64_encode($raw_sig);
        $url = "https://charturl.com/i/" . env('CHARTURL_TOKEN') . "/" . env('CHARTURL_SLUG') . "?d=" . urlencode($json) . "&s=" . urlencode($encoded_sig);
        return $url;
    }

}
