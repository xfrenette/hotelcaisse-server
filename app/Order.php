<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
