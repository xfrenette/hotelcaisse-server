<?php

namespace App\Repositories;

use App\CalculatedValue;

class CalculatedValueRepository
{
    /**
     * @var \App\CalculatedValue
     */
    protected $model;

    /**
     * CalculatedValueRepository constructor.
     *
     * @param \App\CalculatedValue $calculatedValue
     */
    public function __construct(CalculatedValue $calculatedValue)
    {
        $this->model = $calculatedValue;
    }

    /**
     * Saves a the value for the specified `$entity` for the `$key`. If it already exists, it will be replaced.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @param string $key
     * @param float $value
     */
    public function set($instance, $key, $value)
    {
        $this->getQuery($instance, $key)->delete();
        $this->insert($instance, $key, $value);
    }

    /**
     * Returns the value for the `$key` for the specified `$entity`. If it is not yet defined, returns `null` or
     * `$default` if set.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @param string $key
     * @param float $default
     *
     * @return float
     */
    public function get($instance, $key, $default = null)
    {
        $res = $this->getQuery($instance, $key)->value('value');
        return is_null($res) ? $default : floatval($res);
    }

    /**
     * Returns a QueryBuilder for this `$entity` and `$key`.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @param string $key
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getQuery($instance, $key)
    {
        return $this->model
            ->where([
                'instance_id' => $instance->getKey(),
                'class' => get_class($instance),
                'key' => $key,
            ]);
    }

    /**
     * Inserts `$value` in the DB for the `$entity` and `$key`
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @param string $key
     * @param float $value
     */
    protected function insert($instance, $key, $value)
    {
        $this->model->create([
            'instance_id' => $instance->getKey(),
            'key' => $key,
            'value' => $value,
            'class' => get_class($instance),
        ]);
    }
}
