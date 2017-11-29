<?php

namespace App\Http\Middleware\Api;

use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * If the requests contains a `token`, tries to authenticate the device by loading the session before executing the
     * request. If the `token` is valid, the device will be authenticated, else, it won't. The request will then always
     * be executed. This middleware doesn't thrown any error if authentication fails, the only effect is that
     * ApiAuth::check() will return false. If the token is for a valid session, the session is touched (its
     * `expires_at` is bumped)
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = $request->json('token');
        $team = $request->route('team');

        if ($token && $team) {
            if (ApiAuth::loadSession($token, $team)) {
                ApiAuth::touchSession();
            }
        }

        return $next($request);
    }
}
