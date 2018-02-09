<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AdAccount;
use App\Models\AnalyticProperty;
use App\Models\AnalyticView;
use Session;

class AccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        return view('accounts.index', compact('accounts'));
    }

    public function connect()
    {
        $accounts_result = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        $accounts = [];
        if ($accounts_result && count($accounts_result) > 0) {
            foreach ($accounts_result as $account) {
                $accounts[] = $account->type;
            }
        }
        $html = view('ajax.connect_accounts_modal', compact('accounts'))->render();
        return response()->json([
            'status' => 'success',
            'html' => $html,
        ]);
    }

    public function setting($type)
    {
        if ($type == 'facebook') {
            validateTokens();
        }
        $account = Account::where('type', $type)->where('user_id', auth()->user()->id)->where('status', 1)->first();
        if ($account) {
            $ad_accounts = AdAccount::where('account_id', $account->id)->where('is_active', 1)->get();
            $ad_accounts_html = view('ajax.ad_accounts_html', compact('ad_accounts', 'type'))->render();
            return view('accounts.settings', compact('ad_accounts_html', 'type'));
        } else {
            Session::flash('alert-danger', ucfirst($account) . ' not connected. Please connect ' . $account . ' account to update settings.');
            return redirect()->route('accounts.index');
        }
    }

    public function sync($type)
    {
        $html = '';
        if ($type == 'facebook') {
            $fb = fb_connect();
            try {
                $response = $fb->get(
                    '/me/adaccounts?fields=account_id,name',
                    fb_token()
                );
                $adaccounts = $response->getGraphEdge();
                $status = 'success';
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                $status = 'error';
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                $status = 'error';
            }
        }

        if ($type == 'analytics') {
            $client = analytics_connect();
            $client->setAccessToken(analytics_token());
            $analytics = new \Google_Service_Analytics($client);
            try {
                $accounts = $analytics->management_accounts->listManagementAccounts()->getItems();
                $adaccounts = [];
                if (count($accounts) > 0) {
                    foreach ($accounts as $account) {
                        $adaccounts[] = [
                            'id' => $account->getId(),
                            'name' => $account->getName(),
                        ];
                    }
                }
                $status = 'success';
            } catch (\Google_Service_Exception $e) {
                $status = 'error';
            } catch (\Google_Exception $e) {
                $status = 'error';
            }
        }
        if ($type == 'adword') {
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
                ->withDeveloperToken(env('ADWORDS_TOKEN'))
                ->build();
            $adWordsServices = new \Google\AdsApi\AdWords\AdWordsServices();
            $customerService = $adWordsServices->get($session, \Google\AdsApi\AdWords\v201710\mcm\CustomerService::class);
            if ($customerService->getCustomers() && count($customerService->getCustomers()) > 0) {
                $accounts = $customerService->getCustomers();
                $adaccounts = [];
                foreach ($accounts as $account) {
                    $adaccounts[] = [
                        'id' => $account->getCustomerId(),
                        'name' => $account->getDescriptiveName(),
                    ];
                }
                $status = 'success';
            } else {
                $status = 'error';
            }
        }
        if ($status == 'success') {
            $account = Account::where('type', $type)->where('user_id', auth()->user()->id)->where('status', 1)->first();
            if ($adaccounts && count($adaccounts) > 0) {
                $existing_ad_accounts = AdAccount::where('account_id', $account->id)->where('user_id', auth()->user()->id)->get();
                $ad_accounts = [];
                if ($existing_ad_accounts && count($existing_ad_accounts) > 0) {
                    foreach ($existing_ad_accounts as $existing_ad_account) {
                        $ad_accounts[] = $existing_ad_account->ad_account_id;
                    }
                }
                $vendor_ad_accounts = [];
                foreach ($adaccounts as $adaccount) {
                    $vendor_ad_accounts[] = (string) $adaccount['id'];
                    $ad_account_create_array = [
                        'user_id' => auth()->user()->id,
                        'account_id' => $account->id,
                        'title' => $adaccount['name'],
                        'ad_account_id' => (string) $adaccount['id'],
                        'is_active' => 1,
                    ];
                    $local_ad_account = AdAccount::where('account_id', $account->id)
                        ->where('user_id', auth()->user()->id)
                        ->where('ad_account_id', (string) $adaccount['id'])
                        ->first();
                    if ($local_ad_account) {
                        AdAccount::where('account_id', $account->id)
                            ->where('user_id', auth()->user()->id)
                            ->where('ad_account_id', (string) $adaccount['id'])
                            ->update($ad_account_create_array);
                    } else {
                        AdAccount::create($ad_account_create_array);
                    }
                }
                $ad_accounts_diff = array_diff($ad_accounts, $vendor_ad_accounts);
                if ($ad_accounts_diff && count($ad_accounts_diff) > 0) {
                    foreach ($ad_accounts_diff as $ad_account_id) {
                        AdAccount::where('account_id', $account->id)
                            ->where('user_id', auth()->user()->id)
                            ->where('ad_account_id', $ad_account_id)
                            ->update(['is_active' => 0]);
                    }
                }
            } else {
                $html = 'No ad accounts found. Please login to ' . $type . ' and create an ad account.';
            }
            $ad_accounts = AdAccount::where('account_id', $account->id)->where('is_active', 1)->get();
            $this->properties($type, $ad_accounts);
            $html = view('ajax.ad_accounts_html', compact('ad_accounts', 'type'))->render();
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }

    public function properties($type, $ad_accounts)
    {
        if ($ad_accounts && count($ad_accounts) > 0) {
            if ($type == 'analytics') {
                $client = analytics_connect();
                $client->setAccessToken(analytics_token());
                $analytics = new \Google_Service_Analytics($client);
                foreach ($ad_accounts as $ad_account) {
                    try {
                        $properties = $analytics->management_webproperties->listManagementWebproperties($ad_account->ad_account_id)->getItems();
                        if (count($properties) > 0) {
                            $existing_properties = AnalyticProperty::where('ad_account_id', $ad_account->id)->where('user_id', auth()->user()->id)->get();
                            $db_properties = [];
                            if ($existing_properties && count($existing_properties) > 0) {
                                foreach ($existing_properties as $existing_property) {
                                    $db_properties[] = $existing_property->property;
                                }
                            }
                            $ga_properties = [];
                            foreach ($properties as $property) {
                                $ga_properties[] = $property->getId();
                                $property_create_array = [
                                    'user_id' => auth()->user()->id,
                                    'ad_account_id' => $ad_account->id,
                                    'property_id' => $property->internalWebPropertyId,
                                    'property' => $property->getId(),
                                    'name' => $property->name,
                                    'level' => $property->level,
                                    'is_active' => 1,
                                ];
                                $local_property = AnalyticProperty::where('user_id', auth()->user()->id)
                                    ->where('property', $property->getId())
                                    ->first();
                                if ($local_property) {
                                    AnalyticProperty::where('user_id', auth()->user()->id)
                                        ->where('id', $local_property->id)->update($property_create_array);
                                } else {
                                    AnalyticProperty::create($property_create_array);
                                }
                            }
                            $properties_accounts_diff = array_diff($db_properties, $ga_properties);
                            if ($properties_accounts_diff && count($properties_accounts_diff) > 0) {
                                foreach ($properties_accounts_diff as $properties_account_id) {
                                    AnalyticProperty::where('ad_account_id', $properties_account_id)
                                        ->where('user_id', auth()->user()->id)
                                        ->update(['is_active' => 0]);
                                }
                            }
                        }

                        $all_properties = AnalyticProperty::where('user_id', auth()->user()->id)
                            ->where('ad_account_id', $ad_account->id)
                            ->where('is_active', 1)
                            ->get();
                        if ($all_properties && count($all_properties) > 0) {
                            foreach ($all_properties as $analytic_property) {
                                try {
                                    $profiles = $analytics->management_profiles->listManagementProfiles($ad_account->ad_account_id, $analytic_property->property)->getItems();
                                    if (count($profiles) > 0) {
                                        $existing_views = AnalyticView::where('property_id', $analytic_property->id)->where('user_id', auth()->user()->id)->get();
                                        $db_views = [];
                                        if ($existing_views && count($existing_views) > 0) {
                                            foreach ($existing_views as $existing_view) {
                                                $db_views[] = $existing_view->view_id;
                                            }
                                        }

                                        $ga_views = [];
                                        foreach ($profiles as $profile) {
                                            $ga_views[] = $profile->getId();
                                            $view_create_array = [
                                                'user_id' => auth()->user()->id,
                                                'property_id' => $analytic_property->id,
                                                'view_id' => $profile->getId(),
                                                'name' => $profile->name,
                                                'currency' => $profile->currency,
                                                'is_active' => 1,
                                            ];
                                            $local_view = AnalyticView::where('user_id', auth()->user()->id)
                                                ->where('view_id', $profile->getId())->first();
                                            if ($local_view) {
                                                AnalyticView::where('user_id', auth()->user()->id)
                                                    ->where('id', $local_view->id)->update($view_create_array);
                                            } else {
                                                AnalyticView::create($view_create_array);
                                            }
                                        }
                                        $views_accounts_diff = array_diff($db_views, $ga_views);
                                        if ($views_accounts_diff && count($views_accounts_diff) > 0) {
                                            foreach ($views_accounts_diff as $view_id) {
                                                AnalyticView::where('view_id', $view_id)
                                                    ->where('user_id', auth()->user()->id)
                                                    ->update(['is_active' => 0]);
                                            }
                                        }
                                    }
                                    $status = 'success';
                                } catch (\Google_Service_Exception $e) {
                                    $status = 'error';
                                } catch (\Google_Exception $e) {
                                    $status = 'error';
                                }
                            }
                        }
                        $status = 'success';
                    } catch (\Google_Service_Exception $e) {
                        $status = 'error';
                    } catch (\Google_Exception $e) {
                        $status = 'error';
                    }
                }
            }
        }
    }

    public function delete($id)
    {
        $account = Account::where('id', $id)->where('user_id', auth()->id())->first();
        if ($account) {
            $account->status = 0;
            $account->is_active = 0;
            $account->save();
            Session::flash('alert-success', 'Account deleted successfully.');
        } else {
            Session::flash('alert-danger', 'Something went wrong.');
        }
        return redirect()->route('accounts.index');
    }

}
