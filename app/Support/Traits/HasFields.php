<?php

namespace App\Support\Traits;

use App\Field;
use Illuminate\Support\Facades\DB;

trait HasFields
{
    protected $fieldsClass = '';

    /**
     * Name of the field_values table
     * @var string
     */
    protected $fieldsTable = 'field_values';

    /**
     * Name of the class used in the field_values table.
     * @return string
     */
    abstract public function getFieldsClass();

    /**
     * Set the values of Fields for this instance. If the Field already has a value, it is updated, else it is inserted.
     * If we do not want to update existing Fields (ex: we already removed them or the instance is new), we can pass
     * false as a second parameter to skip a request. For each field, the $values array contains a array with 'fieldId'
     * key (a Field id) and a 'value' key for the value.
     *
     * @param array $values
     * @param boolean $checkUpdates
     */
    public function setFieldValues($values, $checkUpdates = true)
    {
        // Get only the ids of the fields to insert/update
        $fieldIds = array_map(function ($fieldData) {
            return $fieldData['fieldId'];
        }, $values);

        // Select existing fields
        if (!$checkUpdates) {
            $existingFields = collect([]);
        } else {
            $existingFields = $this->getFieldsQuery()
                ->whereIn('field_id', $fieldIds)
                ->pluck('field_id');
        }

        $updates = [];
        $inserts = [];

        foreach ($values as $fieldData) {
            $fieldId = $fieldData['fieldId'];

            if ($existingFields->contains($fieldId)) {
                // The field already exists, make an update
                $updates[$fieldId] = $fieldData['value'];
            } else {
                // The field doesn't exist yet, make an insert
                /** @noinspection PhpUndefinedFieldInspection */
                $inserts[] = [
                    'class' => $this->getFieldsClass(),
                    'field_id' => $fieldId,
                    'instance_id' => $this->id,
                    'value' => strval($fieldData['value']),
                ];
            }
        }

        if (count($inserts)) {
            DB::table($this->fieldsTable)
                ->insert($inserts);
        }

        if (count($updates)) {
            foreach ($updates as $fieldId => $value) {
                /** @noinspection PhpUndefinedFieldInspection */
                DB::table($this->fieldsTable)
                    ->where([
                        'class' => $this->getFieldsClass(),
                        'field_id' => $fieldId,
                        'instance_id' => $this->id,
                    ])
                    ->update(['value' => strval($value)]);
            }
        }
    }

    /**
     * Return a Collection of all the field values where each entry is an array with a `fieldId` key (Field id) and a
     * `value` key. Values for numeric fields are converted to number
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFieldValuesAttribute()
    {
        $vt = $this->fieldsTable;
        $ft = with(new Field())->getTable();

        return $this->getFieldsQuery()
            ->select(["$vt.field_id as field_id", "$vt.value as value", "$ft.type as type"])
            ->join($ft, "$ft.id", '=', "$vt.field_id")
            ->get()
            ->map(function ($item) {
                $isNumber = $item->type === 'NumberField';
                return [
                    'fieldId' => $item->field_id,
                    'value' => $isNumber ? floatval($item->value) : $item->value,
                ];
            });
    }

    /**
     * Replaces all the Field values. Old Field that are not present anymore are deleted.
     * @param $values
     */
    public function replaceFieldValues($values)
    {
        $this->getFieldsQuery()->delete();
        $this->setFieldValues($values, false);
    }

    private function getFieldsQuery()
    {
        $t = $this->fieldsTable;

        /** @noinspection PhpUndefinedFieldInspection */
        return DB::table($t)
            ->where([
                "$t.class" => $this->getFieldsClass(),
                "$t.instance_id" => $this->id,
            ]);
    }
}
