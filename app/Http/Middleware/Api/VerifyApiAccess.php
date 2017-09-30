<?php

namespace App\Http\Middleware\Api;

use App\Exceptions\Api\AuthenticationException;
use App\Exceptions\Api\InvalidRequestException;
use App\Support\Facades\ApiAuth;
use App\Team;
use Illuminate\Http\Request;

class VerifyApiAccess
{
    /**
     * Validates that the the device is authenticated (see the Authenticate middleware) and that the associated Team can
     * access the API before executing the request. If not, throws a AuthenticationException exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @throws \App\Exceptions\Api\AuthenticationException
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (!ApiAuth::check()) {
            throw new AuthenticationException('Not authenticated (missing or invalid token)');
        }

        $team = ApiAuth::getTeam();
        if (!$team->canAccessApi()) {
            throw new AuthenticationException('API access denied');
        }

        return $next($request);
    }

    /**
     * First checks if the $team has access to the Api. Then calls ApiAuth::loadSession() with the specified $token and
     * $team. If any error arises, throws an exception
     *
     * @param string $token
     * @param \App\Team|null $team
     *
     * @throws \App\Exceptions\Api\InvalidRequestException
     * @throws \App\Exceptions\Api\AuthenticationException
     */
    protected function validateAuth($token, Team $team = null)
    {
        if (is_null($token)) {
            throw new AuthenticationException('"token" property is missing.');
        }

        if (is_null($team)) {
            throw new InvalidRequestException('Missing Team.');
        }

        if (!$team->canAccessApi()) {
            throw new AuthenticationException('API access denied');
        }

        $result = ApiAuth::loadSession($token, $team);

        if (!$result) {
            throw new AuthenticationException('Invalid token for the team.');
        }
    }
}
