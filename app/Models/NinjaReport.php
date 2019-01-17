<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NinjaReport extends Model
{

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
	
    public function template()
    {
        return $this->hasOne('App\Models\ReportTemplate', 'id', 'template_id');
    }

    public function accounts()
    {
        return $this->hasMany('App\Models\NinjaReportAccount', 'report_id');
    }

}
