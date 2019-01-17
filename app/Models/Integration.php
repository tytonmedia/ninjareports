<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    public $timestamps = false;


    protected $appends = ['logo_url'];
    
    
    public function getLogoUrlAttribute()
    {
        return url($this->logo_path);
    }
}
