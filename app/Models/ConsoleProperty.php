<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsoleProperty extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'siteUrl', 'permissions',
    ];
}