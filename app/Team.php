<?php

namespace App;

use Laravel\Spark\Team as SparkTeam;

class Team extends SparkTeam
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices()
    {
        return $this->hasMany('App\Device');
    }

    /**
     * Returns a query builder that returns all the device approval for devices of this Team.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function deviceApprovals()
    {
        $deviceApprovalsTN = with(new DeviceApproval())->getTable();
        $devicesTN = with(new Device())->getTable();

        return DeviceApproval::select("$deviceApprovalsTN.*")
            ->join($devicesTN, "$deviceApprovalsTN.device_id", '=', "$devicesTN.id")
            ->where("$devicesTN.team_id", $this->id);
    }
}
