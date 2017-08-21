<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'amount'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['uuid', 'amount', 'transactionMode'];

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
    public function register()
    {
        return $this->belongsTo('App\Register');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionMode()
    {
        return $this->belongsTo('App\TransactionMode');
    }

    /**
     * Rename transaction_mode to transactionMode.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        if (array_key_exists('transaction_mode', $array)) {
            $array['transactionMode'] = $array['transaction_mode'];
            unset($array['transaction_mode']);
        }

        return $array;
    }
}
