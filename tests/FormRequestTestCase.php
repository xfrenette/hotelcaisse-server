<?php

namespace Tests;

use Illuminate\Validation\ValidationException;

class FormRequestTestCase extends TestCase
{
    use InteractsWithAPI;

    protected $requestClassName = '';

    public function assertValidates($baseData, $attributeToValidate, $values, $testPresence = true)
    {
        if ($testPresence) {
            $data = $baseData;
            array_forget($data, $attributeToValidate);
            $request = $this->mockFormRequest($this->requestClassName, $data);

            try {
                $request->validate();
                $this->fail('ValidationException not thrown when missing attribute `' . $attributeToValidate . '`');
            } catch (ValidationException $e) {
                // Do nothing
            }
        }

        if (!$values) {
            return;
        }

        foreach ($values as $value) {
            $data = $baseData;
            array_set($data, $attributeToValidate, $value);
            $request = $this->mockFormRequest($this->requestClassName, $data);

            try {
                $request->validate();
                $this->fail('ValidationException not thrown with attribute `'
                    . $attributeToValidate . '` = ' . (is_null($value) ? 'NULL' : '`'.$value.'`'));
            } catch (ValidationException $e) {
                // Do nothing
            }
        }
    }

    public function assertValidatesValidData($data, $optionalAttributes = [])
    {
        // Validate with all the data
        $request = $this->mockFormRequest($this->requestClassName, $data);
        // Should not throw
        try {
            $request->validate();
        } catch (ValidationException $e) {
            $this->fail('Validation failed with valid data');
        }

        // Test by removing each optional attribute
        foreach ($optionalAttributes as $attribute) {
            $newData = $data;
            array_forget($newData, $attribute);
            $request = $this->mockFormRequest($this->requestClassName, $newData);
            // Should not throw
            try {
                $request->validate();
            } catch (ValidationException $e) {
                $this->fail('Validation failed when missing *optional* attribute `' . $attribute . '`');
            }
        }
    }
}
