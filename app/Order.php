<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
     * Redefines the toArray to rename the room_selections key to roomSelections
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (array_key_exists('room_selections', $array)) {
            $array['roomSelections'] = $array['room_selections'];
            unset($array['room_selections']);
        }

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
