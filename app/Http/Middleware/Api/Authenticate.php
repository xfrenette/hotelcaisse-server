<?php

namespace App\Http\Middleware\Api;

use App\Exceptions\Api\AuthenticationException;
use App\Exceptions\Api\InvalidRequestException;
use App\Support\Facades\ApiAuth;
use App\Team;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * Validates that the request has a valid token. If so, loads the session in ApiAuth and regenerates a new token.
     * Else, throws a AuthenticationException exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @throws \App\Exceptions\Api\AuthenticationException
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = $request->json('token');
        $team = $request->route('team');

        $this->validateAuth($token, $team);

        ApiAuth::regenerateToken();

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
