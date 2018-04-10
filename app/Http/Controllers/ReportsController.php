<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AdAccount;
use App\Models\AnalyticProperty;
use App\Models\AnalyticView;
use App\Models\Plan;
use App\Models\Report;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

class ReportsController extends Controller
{
    public function index()
    {
        $current_plan = auth()->user()->current_billing_plan ? auth()->user()->current_billing_plan : 'free_trial';
        $plan = Plan::whereTitle($current_plan)->first();
        $reports_sent_count = Schedule::whereUserId(auth()->user()->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
        $paused = false;
        if ($reports_sent_count >= $plan->reports) {
            $paused = true;
        }
        $all_reports = Report::where('user_id', auth()->user()->id)
            ->where('is_active', 1)
            ->with('account', 'ad_account')
            ->orderBy('id', 'desc')
            ->paginate(15);
        return view('reports.index', compact('all_reports', 'paused'));
    }

    public function tester($account_type)
    {
            $email_substitutions = [];
            $status = 'error';
        if($account_type == 'analytics') {
           sendMail(auth()->user()->email, '[Example] Google Analytics Ninja Report', '05815a19-59be-45be-b111-72a614698248', $email_substitutions);
           $status = 'success';
        } else if($account_type == 'facebook') {
             sendMail(auth()->user()->email, '[Example] Facebook Ads Ninja Report', 'b7326642-541d-4ce0-901a-9a88dacfd07e', $email_substitutions);
             $status = 'success';
        } else if($account_type == 'adword') {
            sendMail(auth()->user()->email, '[Example] Google Adwords Ninja Report', '62a56c1a-1720-49a7-aaf1-8fef14ae00fb', $email_substitutions);
            $status = 'success';
        }
        return response()->json([
            'status' => $status
        ]);
    }
  
    public function create()
    {
        validateTokens();
        $accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();

        $current_plan = auth()->user()->current_billing_plan ? auth()->user()->current_billing_plan : 'free_trial';
        $plan = Plan::whereTitle($current_plan)->first();
        $reports_sent_count = Schedule::whereUserId(auth()->user()->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
        $reports_sent_count = $reports_sent_count > $plan->reports ? $plan->reports : $reports_sent_count;


        $paused = false;
        if ($reports_sent_count >= $plan->reports) {
            $paused = true;
        }
        if ($accounts->count() > 0) {
            return view('reports.create', compact('accounts','paused'));
        } else {
            Session::flash('alert-danger', 'No account connected. Please connect an account to create a report.');
            return redirect()->route('accounts.index');
        }
    }

    public function edit($id)
    {
        validateTokens();
        $accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->get();
        $report = Report::where('id', $id)->where('is_active', 1)->where('user_id', auth()->user()->id)->first();
        if ($report) {
            $account = Account::find($report->account_id);
            $type = $account->type;
            $ad_account_id = $report->ad_account_id;
            $property_id = $report->property_id;
            $profile_id = $report->profile_id;
            $ad_accounts = AdAccount::where('account_id', $report->account_id)
                ->where('user_id', auth()->user()->id)
                ->where('is_active', 1)
                ->get();
            $ad_accounts_html = view('ajax.ad_accounts', compact('ad_accounts', 'type', 'ad_account_id'))->render();
            $properties_html = '';
            if ($property_id) {
                $ad_account = AdAccount::find($ad_account_id);
                $account = $ad_account->property_id;
                $properties = AnalyticProperty::where('ad_account_id', $ad_account_id)
                    ->where('user_id', auth()->user()->id)
                    ->where('is_active', 1)
                    ->get();
                $properties_html = view('ajax.properties', compact('properties', 'type', 'account', 'property_id'))->render();
            }
            $profiles_html = '';
            if ($profile_id) {
                $profiles = AnalyticView::where('property_id', $property_id)
                    ->where('user_id', auth()->user()->id)
                    ->where('is_active', 1)
                    ->get();
                $profiles_html = view('ajax.profiles', compact('profiles', 'type', 'profile_id'))->render();
            }

            $current_plan = auth()->user()->current_billing_plan ? auth()->user()->current_billing_plan : 'free_trial';
            $plan = Plan::whereTitle($current_plan)->first();
            $reports_sent_count = Schedule::whereUserId(auth()->user()->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
            $reports_sent_count = $reports_sent_count > $plan->reports ? $plan->reports : $reports_sent_count;


            $paused = false;
            if ($reports_sent_count >= $plan->reports) {
                $paused = true;
            }
            return view('reports.edit', compact('accounts', 'report', 'ad_accounts_html', 'properties_html', 'profiles_html','paused'));
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
            $ends_at = isset($data->ends_at) && $data->ends_at ? $data->ends_at : $data->ends_at_month . '-' . $data->ends_at_day;
            $next_send_time = make_schedules($data->frequency, $ends_at);
            if ($next_send_time) {
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
                        $ends_at = isset($data->ends_at) && $data->ends_at ? $data->ends_at : $data->ends_at_month . '-' . $data->ends_at_day;
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
                            'next_send_time' => $next_send_time,
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
                    }
                } else {
                    Session::flash('alert-danger', 'Account type not found.');
                }
            } else {
                Session::flash('alert-danger', 'Invalid Date Selection.');
            }
            return redirect()->route('reports.create');
        }
    }

    public function update($id, Request $request)
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
            $ends_at = isset($data->ends_at) && $data->ends_at ? $data->ends_at : $data->ends_at_month . '-' . $data->ends_at_day;
            $next_send_time = make_schedules($data->frequency, $ends_at);
            if ($next_send_time) {
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
                        $report = Report::where('user_id', auth()->user()->id)->where('id', $id)->update([
                            'account_id' => $account_id,
                            'ad_account_id' => $ad_account_id,
                            'property_id' => $property_id,
                            'profile_id' => $profile_id,
                            'title' => $data->title,
                            'frequency' => $data->frequency,
                            'ends_at' => $ends_at,
                            'next_send_time' => $next_send_time,
                            'email_subject' => $data->email_subject,
                            'recipients' => $data->recipients,
                            'attachment_type' => $data->attachment_type,
                        ]);
                        if ($report) {
                            Session::flash('alert-success', 'Report updated successfully.');
                        } else {
                            Session::flash('alert-danger', 'Error creating report. Please try again later.');
                        }
                        return redirect()->route('reports.index');
                    } else {
                        Session::flash('alert-danger', 'Ad Account not found.');
                    }
                } else {
                    Session::flash('alert-danger', 'Account type not found.');
                }
            } else {
                Session::flash('alert-danger', 'Invalid Date Selection.');
            }
            return redirect()->route('reports.edit', $id);
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
                $html = view('ajax.profiles', compact('profiles', 'type'))->render();
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

    public function destroy($id)
    {
        $report = Report::where('id', $id)->where('is_active', 1)->where('user_id', auth()->user()->id)->first();
        if ($report) {
            $report->is_active = 0;
            $report->save();
            session()->flash('alert-success', 'Report Deleted Successfully!');
        } else {
            session()->flash('alert-danger', 'Something went wrong!');
        }
        return redirect()->route('reports.index');
    }

    public function status($id, $is_paused)
    {
        $report = Report::find($id);
        $status = 'error';
        if ($report) {
            $report->is_paused = $is_paused;
            $report->save();
            $status = 'success';
        }
        return response()->json([
            'status' => $status,
        ]);
    }
}
