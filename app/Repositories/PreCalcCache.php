<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PreCalcCache
{
    /**
     * Table name where the pre-calculated cached values are stored
     * @var string
     */
    protected $tableName = 'pre_calc_cache';

    /**
     * Saves a the value for the specified `$entity` for the `$key`. If it already exists, it will be replaced.
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param string $key
     * @param float $value
     */
    public function set($entity, $key, $value)
    {
        $this->getQuery($entity, $key)->delete();
        $this->insert($entity, $key, $value);
    }

    /**
     * Returns the value for the `$key` for the specified `$entity`. If it is not yet defined, returns `null` or
     * `$default` if set.
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param string $key
     * @param float $default
     *
     * @return float
     */
    public function get($entity, $key, $default = null)
    {
        $res = $this->getQuery($entity, $key)->value('value');

        return is_null($res) ? $default : floatval($res);
    }

    /**
     * Returns a QueryBuilder for this `$entity` and `$key`.
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param string $key
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function getQuery($entity, $key)
    {
        return DB::table($this->tableName)
            ->where([
                'entity_id' => $entity->getKey(),
                'key' => $key,
            ]);
    }

    /**
     * Inserts `$value` in the DB for the `$entity` and `$key`
     *
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param string $key
     * @param float $value
     */
    protected function insert($entity, $key, $value)
    {
        DB::table($this->tableName)->insert([
            'entity_id' => $entity->getKey(),
            'key' => $key,
            'value' => $value,
        ]);
    }
}
