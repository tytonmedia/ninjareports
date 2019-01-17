<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'email', 'status', 'token',
    ];

    public function ad_accounts()
    {
        return $this->hasMany('App\Models\AdAccount','account_id');
    }
}
