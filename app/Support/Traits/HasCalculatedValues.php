<?php

namespace App\Support\Traits;

trait HasCalculatedValues
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calculatedValues()
    {
        return $this->hasMany('App\CalculatedValue', 'instance_id')->where([
            'class' => get_class($this),
        ]);
    }

    /**
     * Return the value of the CalculatedValue with the specified `$key`. If not found, returns null.

     * @param string $key
     * @return float
     */
    public function getCalculatedValue($key)
    {
        $calculatedValues = $this->calculatedValues()->get();
        $found = array_first($calculatedValues, function ($calculatedValue) use ($key) {
            return $calculatedValue->key === $key;
        });

        return $found ? $found->value : null;
    }
}
