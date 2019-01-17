<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NinjaReportAccount extends Model
{

	public $timestamps = false;

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
        return $this->hasOne('App\Models\AnalyticProperty', 'id', 'property_id','property');
    }

    public function profile()
    {
        return $this->hasOne('App\Models\AnalyticView', 'id', 'profile_id','name');
    }

    public function getDates()
    {
        return array('created_at', 'updated_at', 'deleted_at', 'sent_at');
    }

}
