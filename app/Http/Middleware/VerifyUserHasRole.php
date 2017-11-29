<?php

namespace App\Http\Middleware;

use Closure;

class VerifyUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param string $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if ($user = $request->user()) {
            if ($user->roleOn($user->currentTeam) === $role) {
                return $next($request);
            }
        }

        return response('Unauthorized.', 401);
    }
}
