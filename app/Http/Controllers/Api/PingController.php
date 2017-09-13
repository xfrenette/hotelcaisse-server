<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Http\Controllers\Controller;

class PingController extends Controller
{
    public function handle()
    {
        return new ApiResponse();
    }
}
