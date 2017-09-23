<?php

namespace App;

use Laravel\Spark\Team as SparkTeam;

class Team extends SparkTeam
{
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
     * Get all of the registers for this team.
     */
    public function registers()
    {
        return $this->hasManyThrough('App\Register', 'App\Device');
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

    /**
     * Returns a Boolean if this Team is allowed to use the Api (ex: if the team has a still valid subscription, if the
     * team has a specific group, etc.). If false is returned, authorization to the API should not be granted.
     *
     * @return bool
     */
    public function canAccessApi()
    {
        // TODO: for now, always return true
        return true;
    }

    /**
     * Tax percentage
     * @return float
     */
    public function taxPercentage() {
        return 14.975;
    }
}
