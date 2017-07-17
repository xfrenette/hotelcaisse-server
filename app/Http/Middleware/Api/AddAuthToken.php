<?php

namespace App\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class AddAuthToken
{
    /**
     * Once the response is generated, if it is an ApiResponse, we add the ApiAuth token to it. In all cases then
     * returns the response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        if ($response instanceof ApiResponse && ApiAuth::check()) {
            $response->setToken(ApiAuth::getToken());
        }

        return $response;
    }
}
