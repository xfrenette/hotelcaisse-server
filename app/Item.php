<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'quantity'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['uuid', 'quantity', 'product'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'float',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('App\ItemProduct', 'item_product_id');
    }

    /**
     * Return the product price multiplied by the quantity
     *
     * @return float
     */
    public function getSubTotalAttribute()
    {
        return floatval(bcmul($this->quantity, $this->product->price));
    }

    /**
     * Returns a Collection of array with `taxId` `name` (Tax name) and `amount` keys (amount is a the unit amount
     * multiplied by the quantity)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTaxesAttribute()
    {
        $productTaxes = $this->product->taxes;
        return $productTaxes->map(function ($tax) {
            $tax['amount'] = floatval(bcmul($tax['amount'], $this->quantity));
            return $tax;
        });
    }

    /**
     * Returns the sum of all taxes
     * @return float
     */
    public function getTaxesTotalAttribute()
    {
        $total = $this->taxes->reduce(function ($total, $tax) {
            return bcadd($total, $tax['amount']);
        }, '0');

        return floatval($total);
    }

    /**
     * Returns the total (sub total + taxes total)
     *
     * @return float
     */
    public function getTotalAttribute()
    {
        return floatval(bcadd($this->subTotal, $this->taxesTotal));
    }

    /**
     * Add `createdAt` timestamp
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'createdAt' => $this->created_at ? $this->created_at->getTimestamp() : null,
        ]);
    }
}
