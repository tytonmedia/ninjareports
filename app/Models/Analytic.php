<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analytic extends Model
{
    protected $fillable = [
        'report_id', 'clicks', 'impressions', 'ctr', 'cpc', 'cpm', 'spend', 'date_start', 'date_stop', 'age', 'gender',
    ];
}
