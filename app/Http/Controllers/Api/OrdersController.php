<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use Illuminate\Http\Request;

class OrdersController extends ApiController
{
    public function new(Request $request)
    {
        $rules = [
            'uuid' => 'bail|required|string|unique:orders',
            'note' => 'sometimes|string',
            'customer' => 'bail|required|array|min:1',
            'customer.fieldValues' => 'bail|required|array|min:1',
            'customer.fieldValues.*.field' => 'bail|required|exists:fields,id',
            'customer.fieldValues.*.value' => 'bail|required|string',
            'credits' => 'sometimes|array',
            'credits.*.uuid' => 'bail|required|string|unique:credits',
            'credits.*.note' => 'bail|required|string',
            'credits.*.amount' => 'bail|required|numeric|not_in:0',
            'transactions' => 'sometimes|array',
            'transactions.*.uuid' => 'bail|required|string|unique:transactions',
            'transactions.*.amount' => 'bail|required|numeric|not_in:0',
            'transactions.*.transactionMode' => 'bail|required|exists:transaction_modes,id',
            'items' => 'sometimes|array',
            'items.*.uuid' => 'bail|required|string|unique:items',
            'items.*.quantity' => 'bail|required|numeric|not_in:0',
            'roomSelections' => 'sometimes|array',
            'roomSelections.*.uuid' => 'bail|required|string|unique:room_selections',
            'roomSelections.*.startDate' => 'bail|required|integer|min:0',
            // 'roomSelections.*.endDate' => // see special validation below
            'roomSelections.*.room' => 'bail|required|exists:rooms,id',
            'roomSelections.*.fieldValues' => 'bail|required|array|min:1',
            'roomSelections.*.fieldValues.*.field' => 'bail|required|exists:fields,id',
            'roomSelections.*.fieldValues.*.value' => 'bail|required|string',
        ];

        $data = $this->getRequestData($request);

        // Conditional validation for items.*.product if it is an id or an array
        $items = array_get($data, 'items', []);

        if (is_array($items)) {
            foreach ($items as $index => $roomSelection) {
                $product = array_key_exists('product', $roomSelection) ? $roomSelection['product'] : null;

                if (!is_array($product)) {
                    // If it is an id
                    $rules["items.$index.product"] = 'bail|required|exists:products,id';
                } else {
                    // If it is an object, we add sub validations
                    $rules["items.$index.product"] = 'bail|required|array';
                    $rules["items.$index.product.uuid"] = 'bail|required|string|unique:products,uuid';
                    $rules["items.$index.product.name"] = 'bail|required|string';
                    $rules["items.$index.product.price"] = 'bail|required|numeric|min:0|not_in:0';
                }
            }
        }

        // Conditional validation for roomSelections.*.endDate to be 1 day after startDate
        $roomSelections = array_get($data, 'roomSelections', []);

        if (is_array($roomSelections)) {
            foreach ($roomSelections as $index => $roomSelection) {
                $startDate = array_key_exists('startDate', $roomSelection) ? $roomSelection['startDate'] : 0;
                $startDate = is_numeric($startDate) ? $startDate : 0;
                $dayAfter = $startDate + (24 * 60 * 60);

                $rules["roomSelections.$index.endDate"] = 'bail|required|integer|min:0' . $dayAfter;
            }
        }

        $this->validate($request, $rules);

        return new ApiResponse();
    }
}
