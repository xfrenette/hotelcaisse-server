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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'number'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['uuid', 'number', 'cashMovements', 'state', 'employee', 'opening_cash'];

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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
         'opening_cash' => 'float',
         'closing_cash' => 'float',
         'post_amount' => 'float',
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
     * Total of transactions with amount >= 0
     *
     * @return float
     */
    public function getPaymentsTotalAttribute()
    {
        return $this->transactions()
            ->where('amount', '>=', 0)
            ->sum('amount');
    }

    /**
     * Absolute (positive) total of transactions with amount < 0
     *
     * @return float
     */
    public function getRefundsTotalAttribute()
    {
        $total = $this->transactions()
            ->where('amount', '<', 0)
            ->sum('amount');

        return $total * -1;
    }

    /**
     * Total of transactions with 'cash' type
     *
     * @return float
     */
    public function getCashTransactionsTotalAttribute()
    {
        return $this->transactions()
            ->join('transaction_modes', 'transactions.transaction_mode_id', '=', 'transaction_modes.id')
            ->where('transaction_modes.type', TransactionMode::TYPE_CASH)
            ->sum('amount');
    }

    /**
     * Total of transactions
     *
     * @return float
     */
    public function getTransactionsTotalAttribute()
    {
        return $this->transactions()
            ->sum('amount');
    }

    /**
     * Total of cash movements
     *
     * @return float
     */
    public function getCashMovementsTotalAttribute()
    {
        return $this->cashMovements()
            ->sum('amount');
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
     * CamelCase attributes and add openedAt
     *
     * @return array
     */
    public function toArray()
    {
        $array = array_camel_case_keys(parent::toArray());
        $array['openedAt'] = $this->opened_at ? $this->opened_at->getTimestamp() : null;

        return $array;
    }

    /**
     * Loads all the relations of this Register
     */
    public function loadAllRelations()
    {
        $this->load('cashMovements');
    }

    /**
     * Returns true if the $modifications array contains at least one Register data specific modification.
     *
     * @param array $modifications
     *
     * @return bool
     */
    public static function containsRelatedModifications($modifications)
    {
        $related = [
            Business::MODIFICATION_REGISTER,
        ];

        foreach ($related as $rel) {
            if (in_array($rel, $modifications)) {
                return true;
            }
        }

        return false;
    }
}
