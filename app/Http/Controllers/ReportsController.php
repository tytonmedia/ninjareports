<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AdAccount;
use App\Models\AnalyticProperty;
use App\Models\AnalyticView;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

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
        $validation_array = [
            'title' => 'required|max:191',
            'account_type' => 'required',
            'frequency' => 'required',
            'recipients' => 'required',
            'attachment_type' => 'required',
            'email_subject' => 'required',
        ];
        if ($request->account_type != '') {
            $validation_array['account'] = 'required';
        }
        if ($request->account_type == 'analytics') {
            $validation_array['property'] = 'required';
            $validation_array['profile'] = 'required';
        }
        $v = Validator::make($request->all(), $validation_array);

        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        } else {
            $data = (object) $request->all();
            $account_id = Account::where('user_id', auth()->user()->id)
                ->where('type', $data->account_type)
                ->pluck('id')
                ->first();
            if ($account_id) {
                $ad_account_id = AdAccount::where('user_id', auth()->user()->id)
                    ->where('account_id', $account_id)
                    ->where('ad_account_id', $data->account)
                    ->where('is_active', 1)
                    ->pluck('id')
                    ->first();
                if ($ad_account_id) {
                    $ends_at = $data->ends_at ? $data->ends_at : $data->ends_at_month . '-' . $data->ends_at_day;
                    $property_id = 0;
                    if (isset($data->property)) {
                        $property_id = AnalyticProperty::where('property', $data->property)
                            ->where('user_id', auth()->user()->id)
                            ->pluck('id')
                            ->first();
                    }
                    $profile_id = 0;
                    if (isset($data->profile)) {
                        $profile_id = AnalyticView::where('view_id', $data->profile)
                            ->where('user_id', auth()->user()->id)
                            ->pluck('id')
                            ->first();
                    }
                    $report = Report::create([
                        'user_id' => auth()->user()->id,
                        'account_id' => $account_id,
                        'ad_account_id' => $ad_account_id,
                        'property_id' => $property_id,
                        'profile_id' => $profile_id,
                        'title' => $data->title,
                        'frequency' => $data->frequency,
                        'ends_at' => $ends_at,
                        'email_subject' => $data->email_subject,
                        'recipients' => $data->recipients,
                        'attachment_type' => $data->attachment_type,
                    ]);
                    if ($report && $report->id) {
                        Session::flash('alert-success', 'Report generated successfully.');
                    } else {
                        Session::flash('alert-danger', 'Error creating report. Please try again later.');
                    }
                    return redirect()->route('reports.index');
                } else {
                    Session::flash('alert-danger', 'Ad Account not found.');
                    return redirect()->route('reports.create');
                }
            } else {
                Session::flash('alert-danger', 'Account type not found.');
                return redirect()->route('reports.create');
            }
        }
    }

    public function ad_accounts($type)
    {
        $html = '';
        $account = Account::where('type', $type)
            ->where('user_id', auth()->user()->id)
            ->where('status', 1)
            ->first();
        if ($account) {
            $ad_accounts = AdAccount::where('account_id', $account->id)
                ->where('user_id', auth()->user()->id)
                ->where('is_active', 1)
                ->get();
            $html = view('ajax.ad_accounts', compact('ad_accounts', 'type'))->render();
            $status = 'success';
        } else {
            $status = 'error';
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }

    public function properties($type, $account)
    {
        $html = '';
        $status = 'error';
        if ($type == 'analytics') {
            $ad_account = AdAccount::where('ad_account_id', $account)
                ->where('user_id', auth()->user()->id)
                ->where('is_active', 1)
                ->first();
            if ($ad_account) {
                $properties = AnalyticProperty::where('ad_account_id', $ad_account->id)
                    ->where('user_id', auth()->user()->id)
                    ->where('is_active', 1)
                    ->get();
                $html = view('ajax.properties', compact('properties', 'type', 'account'))->render();
                $status = 'success';
            } else {
                $status = 'error';
            }
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }

    public function profiles($type, $account, $property)
    {
        $html = '';
        $status = 'error';
        if ($type == 'analytics') {
            $ad_property = AnalyticProperty::where('property', $property)
                ->where('user_id', auth()->user()->id)
                ->where('is_active', 1)
                ->first();
            if ($ad_property) {
                $profiles = AnalyticView::where('property_id', $ad_property->id)
                    ->where('user_id', auth()->user()->id)
                    ->where('is_active', 1)
                    ->get();
                $html = view('ajax.profiles', compact('profiles', 'type', 'account'))->render();
                $status = 'success';
            } else {
                $status = 'error';
            }
        }
        return response()->json([
            'status' => $status,
            'html' => $html,
        ]);
    }

}
