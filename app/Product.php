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
    protected $visible = ['id', 'name', 'price', 'description', 'appliedTaxes'];

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

            $appliedAmount = $tax['type'] === 'absolute' ? $tax['amount'] : ($tax['amount'] / 100) * $price;
            $appliedTaxes->push([
                'tax' => $tax['id'],
                'amount' => $appliedAmount,
            ]);
        });

        return $appliedTaxes;
    }

    /**
     * Returns a collection of all the taxes applicable on this product. It includes default taxes (redefined or note)
     * and non-default taxes that are redefined for this product. For each tax, returns the name, the amount and the
     * type ('percentage' or 'absolute').
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getTaxes()
    {
        $tt = with(new Tax())->getTable();
        $ptt = $this->productTaxTable;

        // Get an array containing each default tax and each redefined tax. Non-default taxes that are not redefined
        // will not be included.
        $query = DB::table($tt)
            ->select(
                "$tt.id as id",
                "$tt.amount as amount",
                "$tt.type as type",
                "$ptt.amount as new_amount",
                "$ptt.type as new_type"
            )
            ->leftJoin($ptt, "$ptt.tax_id", '=', "$tt.id")
            ->where("$tt.business_id", $this->business->id)
            ->where(function ($query) use ($ptt) {
                $query->where("$ptt.product_id", $this->id)
                    ->orWhereNull("$ptt.product_id");
            })
            ->where(function ($query) use ($ptt, $tt) {
                $query->whereNotNull("$ptt.product_id")
                    ->orWhere("$tt.applies_to_all", true);
            });

        // For each row, merge the redefined tax with the default values.
        $taxes = $query->get()->map(function ($tax) {
            return [
                'id' => $tax->id,
                'type' => is_null($tax->new_type) ? $tax->type : $tax->new_type,
                'amount' => is_null($tax->new_amount) ? $tax->amount : $tax->new_amount,
            ];
        });

        return $taxes;
    }

    /**
     * Redefined the toArray to rename `appliedTaxes` to `taxes`, and to make `variants` an array of ids.
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

        $array['variants'] = $this->variants->pluck('id')->toArray();

        return $array;
    }
}
