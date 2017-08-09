<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['id', 'name'];

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
        return $this->belongsTo('App\ProductCategory', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categories()
    {
        return $this->hasMany('App\ProductCategory', 'parent_id');
    }

    /**
     * Returns a Collection of sub Categories containing their sub-categories
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesRecursiveAttribute()
    {
        return $this->categories()->with('categories')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany('App\Product');
    }

    /**
     * Redefines the toArray to add `products` as an array of ids, and to have ensure categories in `categories` also
     * include their sub-categories recursively.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'products' => $this->products->pluck('id')->toArray(),
            'categories' => $this->categoriesRecursive->toArray(),
        ]);
    }
}
