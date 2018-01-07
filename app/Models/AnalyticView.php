<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticView extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'view_id', 'name', 'currency', 'is_active',
    ];
}
