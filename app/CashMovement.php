<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function register()
    {
        return $this->belongsTo('App\Register');
    }
}
