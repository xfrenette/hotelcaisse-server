<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $productTaxTable = 'product_tax';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'price', 'description'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['id', 'name', 'price', 'description', 'appliedTaxes', 'variants'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['appliedTaxes'];

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
    public function parent()
    {
        return $this->belongsTo('App\Product', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany('App\Product', 'parent_id');
    }

    /**
     * Returns a Collection of all the taxes amount applied to the product. For each, an array containing the tax name
     * and the amount for a single product. Taxes redefined with a zero (0) amount are skipped.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAppliedTaxesAttribute()
    {
        $taxes = $this->getTaxes();
        $appliedTaxes = collect();
        $price = $this->price;

        $taxes->each(function ($tax) use ($appliedTaxes, $price) {
            if ($tax['amount'] == 0) {
                return;
            }

            $appliedAmount = $tax['type'] === 'absolute' ? $tax['amount'] : ($tax['amount'] * $price) / 100;
            // To prevent too much decimals because of rounding errors, we limit the number of
            // decimals
            $appliedAmount = round($appliedAmount, 7);
            $appliedTaxes->push([
                'taxId' => $tax['taxId'],
                'amount' => $appliedAmount,
            ]);
        });

        return $appliedTaxes;
    }

    /**
     * Returns a collection of all the taxes applicable on this product. It includes default taxes (redefined or not)
     * and non-default taxes that are redefined for this product. For each tax, returns the name, the amount and the
     * type ('percentage' or 'absolute').
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getTaxes()
    {
        $tt = with(new Tax())->getTable();
        $ptt = $this->productTaxTable;

        /*
         * We want to build an array containing each default tax and each redefined tax. Non-default
         * taxes that are not redefined will not be included. We do this with a sub-query
         * $query will be the following :
         * select
         *   `taxes`.`id` as `id`,
         *   `taxes`.`amount` as `amount`,
         *   `taxes`.`type` as `type`,
         *   `redefined_product_tax`.`amount` as `new_amount`,
         *   `redefined_product_tax`.`type` as `new_type`
         * from
         *   # Select only the redefined taxes of this product (may return nothing)
         *   (select * from `product_tax` where `product_id` = 2) as `redefined_product_tax`
         *   # joins the Tax columns. The `right join` will add any tax not redefined
         *   right join `taxes` on `redefined_product_tax`.`tax_id` = `taxes`.`id`
         * where
         *   # This `where` will eliminate taxes that were not redefined and that don't apply to all
         *   (`redefined_product_tax`.`product_id` is not null or `taxes`.`applies_to_all` = 1)
         *   and `taxes`.`business_id` = ?
         */
        $redefinedProductTaxQuery = DB::table($ptt)
            ->where('product_id', $this->id);

        $query = DB::table(DB::raw("({$redefinedProductTaxQuery->toSql()}) as redefined_product_tax"))
            ->select(
                "$tt.id as id",
                "$tt.amount as amount",
                "$tt.type as type",
                'redefined_product_tax.amount as new_amount',
                'redefined_product_tax.type as new_type'
            )
            ->rightJoin($tt, 'redefined_product_tax.tax_id', '=', "$tt.id")
            ->mergeBindings($redefinedProductTaxQuery)
            ->where("$tt.business_id", $this->business->id)
            ->where(function ($query) use ($tt) {
                $query->where("$tt.applies_to_all", 1)
                    ->orWhereNotNull('redefined_product_tax.product_id');
            });

        // For each row, merge the redefined tax with the default values.
        $taxes = $query->get()->map(function ($tax) {
            return [
                'taxId' => $tax->id,
                'type' => is_null($tax->new_type) ? $tax->type : $tax->new_type,
                'amount' => is_null($tax->new_amount) ? $tax->amount : $tax->new_amount,
            ];
        });

        return $taxes;
    }

    /**
     * Redefined the toArray to rename `appliedTaxes` to `taxes`
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (array_key_exists('appliedTaxes', $array)) {
            $array['taxes'] = $array['appliedTaxes'];
            unset($array['appliedTaxes']);
        }

        return $array;
    }
}
