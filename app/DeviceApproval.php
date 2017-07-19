<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * A DeviceApproval represents an approval for a Device to create a new ApiSession. Note that the Device is already
 * created, and assigned to a Business. The DeviceApproval contains a passcode that must be provided to validate the
 * approval.
 *
 * @package App
 */
class DeviceApproval extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'expires_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo('App\Device');
    }

    /**
     * Returns boolean indicating if this DeviceApproval is expired.
     *
     * @return bool
     */
    public function expired()
    {
        return !$this->expires_at->isFuture();
    }

    /**
     * Sets the expires_at attribute to a time in the past
     */
    public function expire()
    {
        $this->expires_at = Carbon::now()->subSecond(1);
    }

    /**
     * Scope a query to only include non-expired device approvals.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        $tn = $this->getTable();
        return $query->whereDate("$tn.expires_at", '>', Carbon::now());
    }

    /**
     * When setting the passcode, we hash it immediately. Note that this means the passcode can never be retrieved.
     */
    public function setPasscodeAttribute($value)
    {
        $this->attributes['passcode'] = Hash::make($value);
    }
}
