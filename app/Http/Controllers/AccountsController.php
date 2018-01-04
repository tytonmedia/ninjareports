<?php

namespace App\Http\Controllers;

use App\Models\Account;

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
}
