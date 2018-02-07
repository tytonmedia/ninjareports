<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geo extends Model
{
    protected $fillable = [
        'criteria_id', 'name', 'canonical_name', 'parent_id', 'country_code', 'target_type', 'status',
    ];
}
