<?php

namespace App;

use App\Support\Traits\HasFields;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFields;

    /**
     * @see HasFields#getFieldsClass
     * @return string
     */
    protected function getFieldsClass()
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
