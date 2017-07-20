<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * A Device is an API client for a specific Business.
 * @package App
 */
class Device extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * Get the register currently assigned to this device
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentRegister()
    {
        return $this->belongsTo('App\Register', 'register_id');
    }
}
