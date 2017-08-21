<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Business extends Model
{
    const MODIFICATION_REGISTER = 'register';
    const MODIFICATION_ORDERS = 'orders';
    const MODIFICATION_ROOMS = 'rooms';
    const MODIFICATION_TAXES = 'taxes';
    const MODIFICATION_TRANSACTION_MODES = 'transactionModes';
    const MODIFICATION_CATEGORIES = 'categories';
    const MODIFICATION_PRODUCTS = 'products';
    const MODIFICATION_CUSTOMER_FIELDS = 'customerFields';
    const MODIFICATION_ROOM_SELECTION_FIELDS = 'roomSelectionFields';

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = [
        'rooms',
        'transactionModes',
        'products',
        'customerFields',
        'roomSelectionFields',
        'rootProductCategory',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['rootProductCategory'];

    /**
     * Name of the versions table
     * @var string
     */
    protected $versionTable = 'business_versions';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function team()
    {
        return $this->hasOne('App\Team');
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
     * Returns the last version number for this Business. If no version is yet defined, returns null.
     *
     * @return string|null
     */
    public function getVersionAttribute()
    {
        return $this->getVersionsQuery()
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
        $res = $this->getVersionQuery($version)->value('modifications');

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
     * Returns a query builder for the specified version.
     *
     * @param string $version
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getVersionQuery($version)
    {
        return $this->getVersionsQuery()->where('version', $version);
    }

    /**
     * Returns a query builder for all the versions of this Business
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getVersionsQuery()
    {
        return DB::table($this->versionTable)
            ->where('business_id', $this->id);
    }

    /**
     * Returns Collection of version data (`version` and `modifications`) since a $startVersion. The $startVersion's
     * modifications are not included, so returns an empty Collection if $startVersion is the current version. Also
     * returns an empty Collection if the $startVersion does not exist for the current Business.
     *
     * @param string $startVersion
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVersionsSince($startVersion)
    {
        // Get the created_at of the startVersion
        $startVersionDate = $this->getVersionQuery($startVersion)->value('created_at');

        if (is_null($startVersionDate)) {
            return collect([]);
        }

        $res = $this->getVersionsQuery()
            ->select(['version', 'modifications'])
            ->orderBy('created_at', 'asc')
            ->where('created_at', '>=', $startVersionDate)
            ->get();

        // We do not keep the $startVersion or any previous version that would have the exact same created_at, but keep
        // any later version (including ones which would have the exact same created_at)
        $startVersionFound = false;
        $versions = $res->filter(function ($version) use ($startVersion, &$startVersionFound) {
            // We are after the startVersion, we keep it
            if ($startVersionFound) {
                return true;
            }

            // It is the startVersion, we do not keep it
            if ($version->version === $startVersion) {
                $startVersionFound = true;
                return false;
            }

            // We are before the startVersion, we do not keep it
            return false;
        });

        return $versions;
    }

    /**
     * From the result of $this->getVersionsSince($startVersion), returns an array of all the modifications since
     * $startVersion. Contains only unique modifications (duplicate are ignored).
     *
     * @param string $startVersion
     *
     * @return array
     */
    public function getVersionDiff($startVersion)
    {
        $versions = $this->getVersionsSince($startVersion);
        return $versions
            ->pluck('modifications') // get collection of 'modifications' column value
            ->map(function ($modifications) {
                // Transform each modifications string as an array of modifications
                return explode(',', $modifications);
            })
            // All in a single level
            ->flatten()
            // Remove duplicate modifications
            ->unique()
            // Reset the keys
            ->values()
            // return simple array
            ->toArray();
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
     * @param Carbon $createdAt
     */
    public function insertVersion($number, $modifications = [], $createdAt = null)
    {
        if (is_null($createdAt)) {
            $createdAt = Carbon::now();
        }

        DB::table($this->versionTable)->insert([
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
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
            if (!array_key_exists($attributeName, $array)) {
                continue;
            }

            $array[camel_case($attributeName)] = $array[$attributeName];
            unset($array[$attributeName]);
        }

        return $array;
    }

    /**
     * Just a simple utility function that loads the following relations for this Business: rooms, products,
     * transactionModes, customerFields, roomSelectionFields
     */
    public function loadAllRelations()
    {
        $this->load(['rooms', 'transactionModes', 'products', 'customerFields', 'roomSelectionFields']);
    }

    /**
     * Sets the $visible attribute (used in toArray()) from the list of modifications. Ex: if we have the
     * self::MODIFICATION_TAXES, we add the 'taxes' attribute in $visible.
     *
     * @param array $modifications
     */
    public function setVisibleFromModifications($modifications)
    {
        $dict = [
            self::MODIFICATION_ROOMS => 'rooms',
            self::MODIFICATION_TRANSACTION_MODES => 'transactionModes',
            self::MODIFICATION_CATEGORIES => 'rootProductCategory',
            self::MODIFICATION_PRODUCTS => 'products',
            self::MODIFICATION_CUSTOMER_FIELDS => 'customerFields',
            self::MODIFICATION_ROOM_SELECTION_FIELDS => 'roomSelectionFields',
        ];

        $visible = [];

        foreach ($dict as $modification => $attribute) {
            if (in_array($modification, $modifications)) {
                $visible[] = $attribute;
            }
        }

        $this->setVisible($visible);
    }

    /**
     * Returns true if the $modifications array contains at least one Business data specific modification.
     *
     * @param array $modifications
     *
     * @return bool
     */
    public static function containsRelatedModifications($modifications)
    {
        $related = [
            self::MODIFICATION_ROOMS,
            self::MODIFICATION_TAXES,
            self::MODIFICATION_TRANSACTION_MODES,
            self::MODIFICATION_CATEGORIES,
            self::MODIFICATION_PRODUCTS,
            self::MODIFICATION_CUSTOMER_FIELDS,
            self::MODIFICATION_ROOM_SELECTION_FIELDS,
        ];

        foreach ($related as $rel) {
            if (in_array($rel, $modifications)) {
                return true;
            }
        }

        return false;
    }
}
