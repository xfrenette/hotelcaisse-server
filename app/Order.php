<?php

namespace App;

use App\Support\Traits\HasCalculatedValues;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasCalculatedValues;

    const PRE_CALC_SUB_TOTAL = 'order.subTotal';
    const PRE_CALC_TAX = 'order.tax';
    const PRE_CALC_CREDITS = 'order.credits';
    const PRE_CALC_TRANSACTIONS = 'order.transactions';

    /**
     * All relation names, used when loading all the relations
     */
    const RELATIONS = ['customer', 'items.product', 'transactions.transactionMode', 'credits', 'roomSelections.room'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'note'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['uuid', 'note', 'customer', 'items', 'transactions', 'credits', 'roomSelections'];

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
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function credits()
    {
        return $this->hasMany('App\Credit');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany('App\Item');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany('App\Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roomSelections()
    {
        return $this->hasMany('App\RoomSelection');
    }

    /**
     * Returns the total of all items' subtotal (before taxes)
     *
     * @return float
     */
    public function getSubTotalAttribute()
    {
        $this->load('items.product');

        // We use bcmath, so we work with string numbers
        $strRes = $this->items->reduce(function ($subTotal, $item) {
            return bcadd($subTotal, $item->subTotal);
        }, "0");

        return floatval($strRes);
    }

    /**
     * Returns a Collection of the total for each tax. Each element is:
     * 'taxId' : (integer) id of the Tax instance
     * 'name' : (string) name of the tax
     * 'amount' : (float) total for this tax
     *
     * @return Collection
     */
    public function getTaxesAttribute()
    {
        $this->load('items.product');
        $taxes = new Collection();

        $this->items->each(function ($item) use (&$taxes) {
            $item->taxes->each(function ($tax) use (&$taxes) {
                $id = $tax['taxId'];

                if ($taxes->has($id)) {
                    $currentTax = $taxes[$id];
                    $amount = bcadd($currentTax['amount'], $tax['amount']);
                    array_set($currentTax, 'amount', floatval($amount));
                    $taxes[$id] = $currentTax;
                } else {
                    $taxes[$id] = $tax;
                }
            });
        });

        return $taxes->values();
    }

    /**
     * Return the total of all the credits
     *
     * @return float
     */
    public function getCreditsTotalAttribute()
    {
        $total = $this->credits->reduce(function ($prevTotal, $credit) {
            return bcadd($prevTotal, $credit->amount);
        }, '0');

        return floatval($total);
    }

    /**
     * Returns the sum of all the taxes
     *
     * @return float
     */
    public function getTaxesTotalAttribute()
    {
        $total = $this->taxes->reduce(function ($prevTotal, $tax) {
            return bcadd($prevTotal, $tax['amount']);
        }, '0');
        return floatval($total);
    }

    /**
     * Returns the sum of all the transactions
     *
     * @return float
     */
    public function getTransactionsTotalAttribute()
    {
        $total = $this->transactions->reduce(function ($prevTotal, $transaction) {
            return bcadd($prevTotal, $transaction->amount);
        }, '0');

        return floatval($total);
    }

    /**
     * Returns the sum of the sub total and all the taxes minus the credits
     *
     * @return float
     */
    public function getTotalAttribute()
    {
        return floatval(bcadd(
            bcsub($this->subTotal, $this->creditsTotal),
            $this->taxesTotal
        ));
    }

    /**
     * Returns the difference between the total and all the transactions (what is left to pay). A positive value
     * means we must collect the amount, a negative means we must reimburse the amount.
     *
     * @return float
     */
    public function getBalanceAttribute()
    {
        return floatval(bcsub($this->total, $this->transactionsTotal));
    }

    /**
     * Loads all relations (use it before toArray() to have the entire hierarchy)
     */
    public function loadAllRelations()
    {
        $this->load(self::RELATIONS);
    }

    /**
     * Camel case the keys and add the createdAt timestamp
     *
     * @return array
     */
    public function toArray()
    {
        $array = array_camel_case_keys(parent::toArray());
        $array['createdAt'] = $this->created_at ? $this->created_at->getTimestamp() : null;

        return $array;
    }

    /**
     * Select Order that were created after the $from Order
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \App\Order $from
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeFrom($query, Order $from)
    {
        // Next line because of missing type in PHPDoc, see https://github.com/laravel/framework/issues/20546
        /** @noinspection PhpParamsInspection */
        return $query
            // All Orders created *after* $from
            ->where('created_at', '>', $from->created_at)
            // Rare case: For Order with the exact same created_at, we take all that have greater id
            ->orWhere([
                ['created_at', $from->created_at],
                ['id', '>', $from->id],
            ]);
    }
}
