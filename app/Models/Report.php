<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = [
        'user_id', 'account_id', 'ad_account_id', 'property_id', 'profile_id', 'title', 'frequency', 'ends_at', 'email_subject', 'recipients', 'attachment_type', 'sent_at', 'next_send_time', 'is_active',
    ];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function account()
    {
        return $this->hasOne('App\Models\Account', 'id', 'account_id');
    }

    public function ad_account()
    {
        return $this->hasOne('App\Models\AdAccount', 'id', 'ad_account_id');
    }

    public function property()
    {
        return $this->hasOne('App\Models\AnalyticProperty', 'id', 'property_id');
    }

    public function profile()
    {
        return $this->hasOne('App\Models\AnalyticView', 'id', 'profile_id');
    }

    public function getDates()
    {
        return array('created_at', 'updated_at', 'deleted_at', 'sent_at');
    }

}
