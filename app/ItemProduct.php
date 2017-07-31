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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('App\Item');
    }

    /**
     * @todo
     */
    public function getTaxesAttribute()
    {
        return DB::table($this->appliedTaxesTable)
            ->select(['tax_id', 'amount'])
            ->where([
                'type' => 'ItemProduct',
                'instance_id' => $this->id,
            ])->get();
    }

    /**
     * Defines the 'applied taxes' to a single unit of this ItemProduct. $taxes is an array where each element is an
     * array with 'tax_id' key (reference to a Tax) and 'amount' (absolute amount, float).
     * @param array $taxes
     */
    public function setTaxes($taxes)
    {
        $selfID = $this->id;

        $inserts = array_map(function ($taxData) use ($selfID) {
            return [
                'type' => 'ItemProduct',
                'amount' => $taxData['amount'],
                'instance_id' => $selfID,
                'tax_id' => $taxData['tax_id'],
            ];
        }, $taxes);

        DB::table($this->appliedTaxesTable)
            ->insert($inserts);
    }
}
