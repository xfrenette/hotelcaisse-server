<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the DeviceApproval for this Business
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deviceApprovals()
    {
        return $this->hasMany(DeviceApproval::class);
    }
}
