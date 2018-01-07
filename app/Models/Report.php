<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = [
        'user_id', 'account_id', 'ad_account_id', 'property_id', 'profile_id', 'title', 'frequency', 'ends_at', 'email_subject', 'recipients', 'attachment_type',
    ];

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

}
