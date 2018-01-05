<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AdAccount;
use App\Models\Analytic;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;
use \FacebookAds\Http\Exception\AuthorizationException;
use \FacebookAds\Object\Fields\AdsInsightsFields;
use \FacebookAds\Object\Values\AdsInsightsBreakdownsValues;
use \FacebookAds\Object\Values\AdsInsightsDatePresetValues;

class ReportsController extends Controller
{
    public function index()
    {
        $reports = Report::where('user_id', auth()->user()->id)
            ->with('account', 'ad_account')
            ->orderBy('id', 'desc')
            ->paginate(15);
        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        validateTokens();
        $accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        if ($accounts->count() > 0) {
            return view('reports.create', compact('accounts'));
        } else {
            Session::flash('alert-danger', 'No account connected. Please connect an account to create a report.');
            return redirect()->route('accounts.index');
        }
    }

    public function edit($id)
    {
        $report = Report::where('id', $id)
            ->where('user_id', auth()->user()->id)
            ->with('analytics')
            ->first();
        if ($report) {
            pr($report->analytics->toJson(JSON_PRETTY_PRINT));
        } else {
            Session::flash('alert-danger', 'Report not found.');
            return redirect()->route('reports.index');
        }
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|max:191',
            'account_type' => 'required',
            'account' => 'required',
            'frequency' => 'required',
            'recipients' => 'required',
            'attachment_type' => 'required',
            'email_subject' => 'required',
        ]);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        } else {
            $this->getFbData($request->all());
            return redirect()->route('reports.create');

        }
    }

    public function getFbData($request)
    {
        $data = (object) $request;
        switch ($data->frequency) {
            case "weekly":
                $date_preset = AdsInsightsDatePresetValues::THIS_WEEK_MON_TODAY;
                break;
            case "monthly":
                $date_preset = AdsInsightsDatePresetValues::THIS_MONTH;
                break;
            case "yearly":
                $date_preset = AdsInsightsDatePresetValues::LAST_YEAR;
                break;
            default:
                $date_preset = AdsInsightsDatePresetValues::TODAY;
        }
        $fb = fb_connect();
        \FacebookAds\Api::init(env('FACEBOOK_APP_ID'), env('FACEBOOK_SECRET'), fb_token());
        try {
            $fb_ad_account = new \FacebookAds\Object\AdAccount($data->account);
        } catch (\InvalidArgumentException $e) {
            Session::flash('alert-danger', 'Invalid account.');
            return;
        }

        $params = array(
            'date_preset' => $date_preset,
            'breakdowns' => [AdsInsightsBreakdownsValues::AGE, AdsInsightsBreakdownsValues::GENDER],
        );

        $fields = $this->fbDataFields();
        $fields = [$fields->clicks, $fields->impressions, $fields->ctr, $fields->cpc, $fields->cpm, $fields->spend];
        try {
            $insights = $fb_ad_account->getInsights($fields, $params);
        } catch (AuthorizationException $e) {
            Session::flash('alert-danger', $e->getMessage());
            return;
        }
        if ($insights && count($insights) > 0) {
            return $this->generateReport($data, $insights);
        } else {
            Session::flash('alert-danger', 'No data available. Please try again later.');
            return;
        }
    }

    public function getFbAdAccounts()
    {
        $html = '';
        $account = Account::where('type', 'facebook')->where('user_id', auth()->user()->id)->where('status', 1)->first();
        if ($account) {
            $ad_accounts = AdAccount::where('account_id', $account->id)->get();
            $html = view('ajax.fb_ad_accounts', compact('ad_accounts'))->render();
            $status = 'success';
        } else {
            $status = 'error';
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }

    public function generateReport($data, $insights)
    {
        $account_id = Account::where('user_id', auth()->user()->id)
            ->where('type', $data->account_type)
            ->pluck('id')
            ->first();
        if ($account_id) {
            $ad_account_id = AdAccount::where('user_id', auth()->user()->id)
                ->where('account_id', $account_id)
                ->where('ad_account_id', $data->account)
                ->pluck('id')
                ->first();
            if ($ad_account_id) {
                $report = Report::create([
                    'user_id' => auth()->user()->id,
                    'account_id' => $account_id,
                    'ad_account_id' => $ad_account_id,
                    'title' => $data->title,
                    'frequency' => $data->frequency,
                    'email_subject' => $data->email_subject,
                    'recipients' => $data->recipients,
                    'attachment_type' => $data->attachment_type,
                ]);
                if ($report && $report->id) {
                    foreach ($insights as $insight) {
                        Analytic::create([
                            'report_id' => $report->id,
                            'clicks' => $insight->clicks,
                            'impressions' => $insight->impressions,
                            'ctr' => $insight->ctr,
                            'cpc' => $insight->cpc,
                            'cpm' => $insight->cpm,
                            'spend' => $insight->spend,
                            'date_start' => $insight->date_start,
                            'date_stop' => $insight->date_stop,
                            'age' => $insight->age,
                            'gender' => $insight->gender,
                        ]);
                    }
                    Session::flash('alert-success', 'Report generated successfully.');
                    return;
                } else {
                    Session::flash('alert-danger', 'Error creating report. Please try again later.');
                    return;
                }
            } else {
                Session::flash('alert-danger', 'Ad Account not found.');
                return;
            }
        } else {
            Session::flash('alert-danger', 'Account type not found.');
            return;
        }
    }

    public function fbDataFields()
    {
        $fields = [
            'clicks' => AdsInsightsFields::CLICKS,
            'impressions' => AdsInsightsFields::IMPRESSIONS,
            'ctr' => AdsInsightsFields::CTR,
            'cpm' => AdsInsightsFields::CPM,
            'cpc' => AdsInsightsFields::CPC,
            'spend' => AdsInsightsFields::SPEND,
        ];
        return (object) $fields;
    }
}
