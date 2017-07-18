<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A DeviceApproval represents an approval for a Device to create a new ApiSession with the Business. It can reference
 * an existing Device, which will be approved to recreate an ApiSession with the Business, or a null device, which means
 * any existing or new Device will be able to create an ApiSession with this Business. The DeviceApproval contains a
 * passcode that must be provided to validate the approval.
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
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

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
        return $query->whereDate('expires_at', '>', Carbon::now());
    }
}
