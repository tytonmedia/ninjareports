<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

class ReportsController extends Controller
{
    public function index()
    {
        validateTokens();
        $active_accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->count();
        return view('reports.index', compact('active_accounts'));
    }

    public function create()
    {
        $accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        if ($accounts->count() > 0) {
            return view('reports.create', compact('accounts'));
        } else {
            Session::flash('alert-danger', 'No account connected. Please connect an account to create a report.');
            return redirect()->route('accounts.index');
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|max:191',
            'account' => 'required',
            'frequency' => 'required',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v);
        } else {
            $this->getFbData($request->frequency);
        }
    }

    public function getFbData($frequency)
    {
        switch ($frequency) {
            case "weekly":
                $date_preset = \FacebookAds\Object\Values\AdsInsightsDatePresetValues::LAST_7D;
                break;
            case "monthly":
                $date_preset = \FacebookAds\Object\Values\AdsInsightsDatePresetValues::THIS_MONTH;
                break;
            case "yearly":
                $date_preset = \FacebookAds\Object\Values\AdsInsightsDatePresetValues::THIS_YEAR;
                break;
            default:
                $date_preset = \FacebookAds\Object\Values\AdsInsightsDatePresetValues::TODAY;
        }
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->first();
        $fb = fb_connect();
        \FacebookAds\Api::init(env('FACEBOOK_APP_ID'), env('FACEBOOK_SECRET'), $account->token);
        $account = new \FacebookAds\Object\AdAccount('act_1725508151036270');
        $params = array(
            'date_preset' => $date_preset,
        );
        $clicks = \FacebookAds\Object\Fields\AdsInsightsFields::CLICKS;
        $impressions = \FacebookAds\Object\Fields\AdsInsightsFields::IMPRESSIONS;
        $ctr = \FacebookAds\Object\Fields\AdsInsightsFields::CTR;
        $cpm = \FacebookAds\Object\Fields\AdsInsightsFields::CPM;
        $cpc = \FacebookAds\Object\Fields\AdsInsightsFields::CPC;
        $spend = \FacebookAds\Object\Fields\AdsInsightsFields::SPEND;
        $insights = $account->getInsights(array(
            $clicks, $impressions, $ctr, $cpc, $cpm, $spend,
        ), $params);
        foreach ($insights as $insight) {
            echo 'Clicks: ' . $insight->{$clicks} . '<br/>';
            echo 'Impressions: ' . $insight->{$clicks} . '<br/>';
            echo 'Impressions: ' . $insight->{$impressions} . '<br/>';
            echo 'CTR: ' . $insight->{$ctr} . '<br/>';
            echo 'CPM: ' . $insight->{$cpm} . '<br/>';
            echo 'CPC: ' . $insight->{$cpc} . '<br/>';
            echo 'Spend: ' . $insight->{$spend} . '<br/>';
            echo '<a href="' . route('reports.create') . '">Go Back</a>';
        }
        exit;
    }
}
