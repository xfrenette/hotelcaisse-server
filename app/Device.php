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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['currentRegister'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apiSessions()
    {
        return $this->hasMany('App\ApiSession');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvals()
    {
        return $this->hasMany('App\DeviceApproval');
    }

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
        $this->apiSessions()->delete();
    }

    /**
     * Deletes all DeviceApprovals for this Device
     */
    public function clearApprovals()
    {
        $this->approvals()->delete();
    }

    /**
     * Creates a new DeviceApproval with the specified $passcode for this Device
     *
     * @param string $passcode
     * @return \App\DeviceApproval
     */
    public function createApproval($passcode)
    {
        $approval = new DeviceApproval(['passcode' => $passcode]);
        $approval->device()->associate($this);
        $approval->save();

        return $approval;
    }

    /**
     * Camel case attributes
     * @return array
     */
    public function toArray()
    {
        return array_camel_case_keys(parent::toArray());
    }


}
