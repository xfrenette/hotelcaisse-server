<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * An ApiSession is an access granted for a device to the API.
 * @package App
 */
class ApiSession extends Model
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
     * Returns boolean indicating if this ApiSession is expired.
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
     * Scope a query to only include non-expired api sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->whereDate('expires_at', '>', Carbon::now());
    }
}
