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
     * Get the DeviceApproval for this Business
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deviceApprovals()
    {
        return $this->hasMany(DeviceApproval::class);
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
