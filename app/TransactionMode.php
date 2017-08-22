<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionMode extends Model
{
    use SoftDeletes;

    const TYPE_CASH = 'cash';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'type'];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = ['id', 'name', 'type', 'archived'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['archived'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * return boolean
     */
    public function getArchivedAttribute()
    {
        return $this->trashed();
    }
}
