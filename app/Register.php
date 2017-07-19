<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    /**
     * State when closed
     */
    const STATE_CLOSED = 0;
    /**
     * State when opened
     */
    const STATE_OPENED = 1;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'modified_at',
        'opened_at',
        'closed_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo('App\Device');
    }

    /**
     * Opens the register and sets the related attributes.
     *
     * @param string $employee
     * @param float $cashAmount
     */
    public function open($employee, $cashAmount)
    {
        $this->state = self::STATE_OPENED;
        $this->employee = $employee;
        $this->openingCash = $cashAmount;
        $this->openedAt = Carbon::now();
    }

    /**
     * Closes the register and sets the related attributes.
     *
     * @param float $cashAmount
     * @param string $POSTRef
     * @param float $POSTAmount
     */
    public function close($cashAmount, $POSTRef, $POSTAmount)
    {
        $this->state = self::STATE_CLOSED;
        $this->closingCash = $cashAmount;
        $this->POSTRef = $POSTRef;
        $this->POSTAmount = $POSTAmount;
        $this->closedAt = Carbon::now();
    }

    /**
     * Returns true if the register is opened, else returns false.
     * Use as attribute $register->opened
     *
     * @return bool
     */
    public function getOpenedAttribute()
    {
        return $this->state === self::STATE_OPENED;
    }
}
