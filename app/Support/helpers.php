<?php

if (!function_exists('array_camel_case_keys')) {
    /**
     * Returns an array where all the keys are camelCased
     *
     * @param array $array
     * @return array
     */
    function array_camel_case_keys($array)
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            $newArray[camel_case($key)] = $value;
        }

        return $newArray;
    }
}
