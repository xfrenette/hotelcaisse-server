<?php

namespace App\Http\Middleware\Api;

use App\Exceptions\Api\InvalidRequestException;
use Closure;
use Illuminate\Http\Request;

class ValidateRequest
{
    /**
     * Validates that the request is a JSON object. If not, throws an exception.
     *
     * @param Request $request
     * @param Closure $next
     * @throws InvalidRequestException
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isJson()) {
            throw new InvalidRequestException('Request does not contain JSON Content-Type');
        }

        return $next($request);
    }
}
