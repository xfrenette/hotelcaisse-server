<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Business extends Model
{
    const MODIFICATION_REGISTER = 'register';
    const MODIFICATION_ORDERS = 'orders';

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = [
        'rooms',
        'taxes',
        'transaction_modes',
        'products',
        'customer_fields',
        'room_selection_fields',
        'root_product_category',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['root_product_category'];

    /**
     * Name of the versions table
     * @var string
     */
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rooms()
    {
        return $this->hasMany('App\Room');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxes()
    {
        return $this->hasMany('App\Tax');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionModes()
    {
        return $this->hasMany('App\TransactionMode');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany('App\Product');
    }

    /**
     * All the customer Fields
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function customerFields()
    {
        return $this->belongsToMany('App\Field', 'business_fields', 'business_id', 'field_id')
            ->wherePivot('type', 'customer');
    }

    /**
     * All the roomSelection Fields
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roomSelectionFields()
    {
        return $this->belongsToMany('App\Field', 'business_fields', 'business_id', 'field_id')
            ->wherePivot('type', 'roomSelection');
    }

    /**
     * Returns the root ProductCategory, or null if none. We find it by looking for a ProductCategory assigned to this
     * business that doesn't have a parent.
     *
     * @return \App\ProductCategory|null
     */
    public function getRootProductCategoryAttribute()
    {
        return ProductCategory::where([
            'business_id' => $this->id,
            'parent_id' => null,
        ])->first();
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
     * Returns the list of modifications for a single version (array of string). If the version has no modifications, an
     * empty array is returned. If the version doesn't exist for the business, returns null.
     *
     * @param $version
     *
     * @return array|null
     */
    public function getVersionModifications($version)
    {
        $res = DB::table($this->versionTable)
            ->where('business_id', $this->id)
            ->where('version', $version)
            ->value('modifications');

        if (is_null($res)) {
            return null;
        }

        // If empty string, return empty array (explode() would have a return an array with an empty string)
        if (empty($res)) {
            return [];
        }

        return explode(',', $res);
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
        $this->insertVersion($newVersion, $modifications);
        return $newVersion;
    }

    /**
     * Inserts in the DB the version $number with the $modifications list.
     *
     * @param string $number
     * @param array $modifications
     */
    public function insertVersion($number, $modifications = [])
    {
        DB::table($this->versionTable)->insert([
            'created_at' => Carbon::now()->format('Y-m-d H:m:s'),
            'business_id' => $this->id,
            'version' => $number,
            'modifications' => implode(',', $modifications),
        ]);
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

    /**
     * Redefines the toArray to camelCase the `customer_fields`, `room_selection_fields` and 'transaction_modes'.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $toCamelCase = ['customer_fields', 'room_selection_fields', 'transaction_modes', 'root_product_category'];

        foreach ($toCamelCase as $attributeName) {
            $array[camel_case($attributeName)] = $array[$attributeName];
            unset($array[$attributeName]);
        }

        return $array;
    }
}
