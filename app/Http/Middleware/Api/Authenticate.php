<?php

namespace App\Http\Middleware\Api;

use App\Business;
use App\Exceptions\Api\InvalidRequestException;
use App\Exceptions\Api\InvalidTokenException;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * Validates that the request has a valid token. If so, loads the session in ApiAuth and regenerates a new token.
     * Else, throws a InvalidTokenException exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @throws \App\Exceptions\Api\InvalidTokenException
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = $request->json('token');
        $business = $request->route('business');

        $this->validateAuth($token, $business);

        ApiAuth::regenerateToken();

        return $next($request);
    }

    /**
     * Calls ApiAuth::loadSession() with the specified $token and $business. If either is null or if we could not load
     * the session, throw an error. Else do nothing.
     *
     * @param string $token
     * @param \App\Business|null $business
     *
     * @throws \App\Exceptions\Api\InvalidRequestException
     * @throws \App\Exceptions\Api\InvalidTokenException
     */
    protected function validateAuth($token, Business $business = null)
    {
        if (is_null($token)) {
            throw new InvalidTokenException('"token" property is missing.');
        }

        if (is_null($business)) {
            throw new InvalidRequestException('Missing business.');
        }

        $result = ApiAuth::loadSession($token, $business);

        if (!$result) {
            throw new InvalidTokenException('Invalid token for the business.');
        }
    }
}
