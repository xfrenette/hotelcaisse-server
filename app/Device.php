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
    public function team()
    {
        return $this->belongsTo('App\Team');
    }

    /**
     * Get the register currently assigned to this device
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentRegister()
    {
        return $this->belongsTo('App\Register', 'register_id');
    }

    /**
     * Simple utility function that returns true if this Device has a currentRegister that is opened. In all other
     * cases, returns false (ex: no currentRegister).
     *
     * @return bool
     */
    public function getIsCurrentRegisterOpenedAttribute()
    {
        if (is_null($this->currentRegister)) {
            return false;
        }

        return $this->currentRegister->state === Register::STATE_OPENED;
    }
}
