<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAccount extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'title', 'ad_account_id',
    ];
}
