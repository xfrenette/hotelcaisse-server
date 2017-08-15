<?php

namespace App;

use App\Support\Traits\HasFields;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFields;

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['fieldValues'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['fieldValues'];

    /**
     * @see HasFields#getFieldsClass
     * @return string
     */
    public function getFieldsClass()
    {
        return 'Customer';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }
}
