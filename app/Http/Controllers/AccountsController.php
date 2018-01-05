<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AdAccount;
use Session;

class AccountsController extends Controller
{
    public function index()
    {
        $accounts = Account::where('user_id', auth()->user()->id)->get();
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

    public function facebookSettings()
    {
        validateTokens();
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->where('status', 1)->first();
        if ($account) {
            $ad_accounts = AdAccount::where('account_id', $account->id)->get();
            $facebook_ad_accounts_html = view('ajax.fb_ad_accounts_html', compact('ad_accounts'))->render();
            return view('accounts.settings.facebook', compact('facebook_ad_accounts_html'));
        } else {
            Session::flash('alert-danger', 'Facebook not connected. Please connect facebook account to update settings.');
            return redirect()->route('accounts.index');
        }
    }

    public function syncFacebookAdAccounts()
    {
        $fb = fb_connect();
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $fb->get(
                '/me/adaccounts?fields=account_id,name',
                fb_token()
            );
            $status = 'success';
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $status = 'error';
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $status = 'error';
        }
        $html = '';
        if ($status == 'success') {
            $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->where('status', 1)->first();
            $adaccounts = $response->getGraphEdge();
            if ($adaccounts && count($adaccounts) > 0) {
                AdAccount::where('account_id', $account->id)->where('user_id', auth()->user()->id)->delete();
                foreach ($adaccounts as $adaccount) {
                    $ad_account_create_array = [
                        'user_id' => auth()->user()->id,
                        'account_id' => $account->id,
                        'title' => $adaccount['name'],
                        'ad_account_id' => $adaccount['id'],
                    ];
                    AdAccount::create($ad_account_create_array);
                }
            } else {
                $html = 'No ad accounts found. Please login to facebook and create an ad account.';
            }
            $ad_accounts = AdAccount::where('account_id', $account->id)->get();
            $html = view('ajax.fb_ad_accounts_html', compact('ad_accounts'))->render();
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }
}
