<?php

namespace App\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Support\Facades\ApiAuth;
use Closure;

class AddDataVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof ApiResponse && ApiAuth::check()) {
            $business = ApiAuth::getBusiness();
            $response->setDataVersion($business->version);
        }

        return $response;
    }
}
