<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Business extends Model
{
    protected $versionTable = 'business_versions';

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
     * Returns a query builder that returns all the device approval for devices of this business.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function deviceApprovals()
    {
        $deviceApprovalsTN = with(new DeviceApproval())->getTable();
        $devicesTN = with(new Device())->getTable();

        return DeviceApproval::select("$deviceApprovalsTN.*")
            ->join($devicesTN, "$deviceApprovalsTN.device_id", '=', "$devicesTN.id")
            ->where("$devicesTN.business_id", $this->id);
    }

    /**
     * Returns the last version number for this Business. If no version is yet defined, returns null.
     *
     * @return string|null
     */
    public function getVersionAttribute()
    {
        return DB::table($this->versionTable)
            ->where('business_id', $this->id)
            ->orderBy('id', 'desc')
            ->latest()
            ->value('version');
    }

    /**
     * Bumps the version of the Business and saves the list of modifications for it.
     *
     * @param array $modifications Array of attributes name
     * @return string
     */
    public function bumpVersion($modifications = [])
    {
        $newVersion = $this->generateNextVersion();
        DB::table($this->versionTable)->insert([
            'created_at' => Carbon::now()->format('Y-m-d H:m:s'),
            'business_id' => $this->id,
            'version' => $newVersion,
            'modifications' => implode(',', $modifications),
        ]);
        return $newVersion;
    }

    /**
     * Generates the next version based on the current one.
     *
     * @return string
     */
    protected function generateNextVersion()
    {
        $currentVersion = $this->version;
        $newVersion = '1';

        if (!is_null($currentVersion)) {
            $newVersion = (string) (((integer) $currentVersion) + 1);
        }

        return $newVersion;
    }
}
