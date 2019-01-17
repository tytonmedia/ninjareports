<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateIntegration extends Model
{
    protected $table='report_template_integration';
    public $timestamps = false;
    
    public function integration()
    {
        return $this->hasOne('App\Models\Integration');
    }

    public function reportTemplate()
    {
        return $this->belongsTo('App\Models\ReportTemplate');
    }
}
