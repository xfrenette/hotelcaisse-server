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
    protected $visible = ['currentRegister', 'nextRegisterNumber'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['nextRegisterNumber'];

    /**
     * Default values for attributes
     *
     * @var array
     */
    protected $attributes = [
        'initial_register_number' => 1,
    ];

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registers()
    {
        return $this->hasMany('App\Register');
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
     * Returns the next register number to be used based on the latest open register. If there is no register,
     * returns the value of initial_register_number
     *
     * @return integer
     */
    public function getNextRegisterNumberAttribute()
    {
        $lastNumber = $this->registers()->orderBy('opened_at', 'desc')->value('number');

        return is_null($lastNumber) ? $this->initial_register_number : $lastNumber + 1;
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
     * Camel case attributes. If no currentRegister, explicitly set it to null
     * @return array
     */
    public function toArray()
    {
        $array = array_camel_case_keys(parent::toArray());

        if (!array_key_exists('currentRegister', $array) && $this->currentRegister()->count() === 0) {
            $array['currentRegister'] = null;
        }

        return $array;
    }

    /**
     * Loads all relations required by toArray() to return a complete object
     */
    public function loadToArrayRelations()
    {
        $this->load('currentRegister');

        if ($this->currentRegister) {
            $this->currentRegister->loadAllRelations();
        }
    }

    /**
     * Returns true if the $modifications array contains at least one Device data (or Register)
     * specific modification.
     *
     * @param array $modifications
     *
     * @return bool
     */
    public static function containsRelatedModifications($modifications)
    {
        // For now, only checks for Register modification
        return Register::containsRelatedModifications($modifications);
    }
}
