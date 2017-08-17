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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['passcode'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->touch();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo('App\Device');
    }

    /**
     * Sets the expires_at date $lifetime seconds in the future. If $lifetime is not set, uses the config
     * `api.deviceApprovals.defaultLifetime`.
     *
     * @param integer|null $lifetime
     */
    public function touch($lifetime = null)
    {
        if (is_null($lifetime)) {
            $lifetime = config('api.deviceApprovals.defaultLifetime');
        }

        $expiresAt = Carbon::now()->addSeconds($lifetime);
        $this->until($expiresAt);
    }

    /**
     * Simple utility function to set the the `expires_at` date
     *
     * @param $date
     */
    public function until($date)
    {
        $this->expires_at = $date;
    }

    /**
     * Returns true if the $passcode matches the one of this DeviceApproval.
     *
     * @param $passcode
     *
     * @return bool
     */
    public function check($passcode)
    {
        return Hash::check($passcode, $this->passcode);
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
        return $query->where("$tn.expires_at", '>', Carbon::now());
    }

    /**
     * When setting the passcode, we hash it immediately. Note that this means the passcode can never be retrieved.
     */
    public function setPasscodeAttribute($value)
    {
        $this->attributes['passcode'] = Hash::make($value);
    }

    /**
     * Generates a passcode
     *
     * @return string
     */
    public static function generatePasscode()
    {
        $digits = 4;
        return str_pad(rand(1, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
    }
}
