<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticProperty extends Model
{
    protected $fillable = [
        'user_id', 'ad_account_id', 'property_id', 'property', 'name', 'level', 'is_active',
    ];
}
