<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalculatedValue extends Model
{
    protected $fillable = ['instance_id', 'class', 'key', 'value'];
}
