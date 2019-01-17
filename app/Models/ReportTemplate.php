<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    //
    public $timestamps = false;
    protected $appends = ['logo_url'];

    public function integrations()
    {
        return $this->belongsToMany('App\Models\Integration','report_template_integration');
    }
    
    public function getLogoUrlAttribute()
    {
        return url($this->logo_path);
    }
}
