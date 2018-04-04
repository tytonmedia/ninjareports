<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Plan;
use App\Models\Schedule;
use date;
use Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        // $this->middleware('subscribed');
    }

    /**
     * Show the application dashboard.
     *
     * @return Response
     */
    public function show()
    {

        $current_plan = auth()->user()->current_billing_plan ? auth()->user()->current_billing_plan : 'free_trial';
        $plan = Plan::whereTitle($current_plan)->first();
        $reports_sent_count = Schedule::whereUserId(auth()->user()->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
        $reports_sent_count = $reports_sent_count > $plan['reports'] ? $plan['reports'] : $reports_sent_count;
        $active_accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->count();

        $paused = false;
        if ($reports_sent_count >= $plan['reports']) {
            $paused = true;
        }
        return view('home', compact('active_accounts', 'plan', 'reports_sent_count','paused'));
    }
}
