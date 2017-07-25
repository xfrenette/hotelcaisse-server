<?php

namespace App;

use App\Support\Traits\HasFields;
use Illuminate\Database\Eloquent\Model;

class RoomSelection extends Model
{
    use HasFields;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'start_date',
        'end_date',
    ];

    /**
     * @see HasFields#getFieldsClass
     * @return string
     */
    protected function getFieldsClass()
    {
        return 'Customer';
    }

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
    public function room()
    {
        return $this->belongsTo('App\Room');
    }
}
