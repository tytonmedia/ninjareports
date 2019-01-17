<?php

namespace App\Services;

use App\Models\Account;
use App\User;
 
class UserService
{
    
    public function getActiveIntegrationsOfUser(User $user)
    {
        return Account::where('user_id',$user->id)->where('status', 1)->get();
    }
    public function checkAccount($account_type){
        $account_id = Account::where('user_id', auth()->user()->id)
                    ->where('type', $account_type)
                    ->pluck('id')
                    ->first();
        return $account_id;
    }
    
}