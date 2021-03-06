<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * An ItemProduct represents the original product that is in an Item. Products can be changed or deleted over time, so
 * we need to keep information on how the Product of the Item was at the time of the Order, so we create an ItemProduct.
 * @package App
 */
class ItemProduct extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'price', 'product_id'];

    /**
     * Table name for applied_taxes
     * @var string
     */
    protected $appliedTaxesTable = 'applied_taxes';

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['name', 'price', 'taxes'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['taxes'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    /**
     * Returns a Collection of array with `taxId` `name` (Tax name) and `amount` keys
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTaxesAttribute()
    {
        $att = $this->appliedTaxesTable;
        $tt = with(new Tax())->getTable();

        return DB::table($att)
            ->select(["$att.tax_id as taxId", "$att.amount as amount", "$tt.name as name"])
            ->join($tt, "$att.tax_id", '=', "$tt.id")
            ->where([
                "$att.type" => 'ItemProduct',
                "$att.instance_id" => $this->id,
            ])->get()
            ->map(function ($row) {
                return get_object_vars($row);
            });
    }

    /**
     * Defines the 'applied taxes' to a single unit of this ItemProduct. $taxes is an array where each element is an
     * array with 'tax_id' key (reference to a Tax) and 'amount' (absolute amount, float).
     *
     * @param array $taxes With keys `taxId`(integer, tax id) and `amount` (float, absolute amount)
     */
    public function setTaxes($taxes)
    {
        $selfID = $this->id;

        $inserts = array_map(function ($taxData) use ($selfID) {
            return [
                'type' => 'ItemProduct',
                'amount' => $taxData['amount'],
                'instance_id' => $selfID,
                'tax_id' => $taxData['taxId'],
            ];
        }, $taxes);

        DB::table($this->appliedTaxesTable)
            ->insert($inserts);
    }

    /**
     * Camel case keys and add product_id as `id`
     *
     * @return array
     */
    public function toArray()
    {
        $array = array_camel_case_keys(parent::toArray());
        $array['id'] = $this->product_id;

        return $array;
    }
}
