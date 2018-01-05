<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'ad_account_id', 'title', 'frequency', 'email_subject', 'recipients', 'attachment_type',
    ];

    public function account(){
        return $this->hasOne('App\Models\Account', 'id', 'account_id');
    }

    public function ad_account(){
        return $this->hasOne('App\Models\AdAccount', 'id', 'ad_account_id');
    }

    public function analytics(){
        return $this->hasMany('App\Models\Analytic');
    }
}
