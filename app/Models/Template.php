<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
	/**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'template';



    /**
     * The database primary key value.
     *
     * @var string
     */
    protected $primaryKey = 'id';
	
    protected $fillable = [
        'id', 'name', 'template_id', 'api','template_url','account_type','description','status'
    ];
}
