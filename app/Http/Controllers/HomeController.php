<?php

namespace App\Http\Controllers;

use App\Models\Account;

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
        $active_accounts = Account::where('user_id', auth()->user()->id)->where('status', 1)->count();
        return view('home', compact('active_accounts'));
    }
}
