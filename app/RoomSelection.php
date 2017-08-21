<?php

namespace App;

use App\Support\Traits\HasFields;
use Illuminate\Database\Eloquent\Model;

class RoomSelection extends Model
{
    use HasFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'start_date', 'end_date'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['uuid', 'fieldValues', 'room'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['fieldValues'];

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
    public function getFieldsClass()
    {
        return 'RoomSelection';
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

    /**
     * Redefined the toArray to add startDate and endDate timestamps, and room (Room id) attribute.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'startDate' => $this->start_date ? $this->start_date->getTimestamp() : null,
            'endDate' => $this->end_date ? $this->end_date->getTimestamp() : null,
        ]);
    }
}
