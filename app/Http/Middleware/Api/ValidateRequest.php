<?php

namespace App\Http\Middleware\Api;

use App\Exceptions\Api\InvalidRequestException;
use Closure;
use Illuminate\Http\Request;

class ValidateRequest
{
    /**
     * Validates that the request is a JSON request. Also checks that, if a body is present, it is valid JSON. Else
     * throws an InvalidRequestException.
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

        // If the content is not empty, test is valid json
        $content = $request->getContent();

        if (!empty(trim($content))) {
            json_decode($request->getContent());

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidRequestException('Request does not contain valid JSON');
            }
        }

        return $next($request);
    }
}
