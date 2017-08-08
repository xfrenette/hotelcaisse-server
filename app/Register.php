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
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['cashMovements'];

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cashMovements()
    {
        return $this->hasMany('App\CashMovement');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('App\Transaction');
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
        $this->opening_cash = $cashAmount;
        $this->opened_at = Carbon::now();
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
        $this->closing_cash = $cashAmount;
        $this->post_ref = $POSTRef;
        $this->post_amount = $POSTAmount;
        $this->closed_at = Carbon::now();
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

    /**
     * Redefines the toArray to rename `cash_movements` to `cashMovements`
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['cashMovements'] = $array['cash_movements'];
        unset($array['cash_movements']);

        return $array;
    }
}
