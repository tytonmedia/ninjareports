<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'email', 'status', 'token',
    ];
}
