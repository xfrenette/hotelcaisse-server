<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalculatedValue extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['instance_id', 'class', 'key', 'value'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'value' => 'float',
    ];
}
