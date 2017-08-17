<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A Device is an API client for a specific Business.
 * @package App
 */
class Device extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

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

    /**
     * Logouts this Device from any active session
     */
    public function logout()
    {
        $apiSessionsTable = with(new ApiSession())->getTable();

        DB::table($apiSessionsTable)->where('device_id', $this->id)->delete();
    }
}
