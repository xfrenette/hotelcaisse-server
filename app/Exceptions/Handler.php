<?php

namespace App\Exceptions;

use App\Api\Http\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        /**
         * API exceptions
         */
        if ($this->isApi($request)) {
            return $this->renderAsApiResponse($request, $exception);
        }

        /**
         * Web exceptions
         */

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Render an exception as an API response object
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \App\Api\Http\ApiResponse
     */
    protected function renderAsApiResponse($request, Exception $exception)
    {
        if ($exception instanceof Api\InvalidTokenException) {
            return $this->apiResponseError(401, ApiResponse::ERROR_AUTH_FAILED);
        }

        if ($exception instanceof Api\InvalidRequestException) {
            return $this->apiResponseError(400, ApiResponse::ERROR_INVALID_REQUEST);
        }

        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException) {
            return $this->apiResponseError(404, ApiResponse::ERROR_NOT_FOUND);
        }

        if ($exception instanceof HttpException) {
            // For other 4xx HTTP exceptions, we return a client error with the correct HTTP error
            // code
            if ($exception->getStatusCode() >= 400 && $exception->getStatusCode() < 500) {
                return $this->apiResponseError(
                    $exception->getStatusCode(),
                    ApiResponse::ERROR_CLIENT_ERROR
                );
            }

            // For other HTTP exceptions, we return a server error with the correct HTTP error code
            return $this->apiResponseError(
                $exception->getStatusCode(),
                ApiResponse::ERROR_NOT_FOUND
            );
        }

        // All other exceptions
        return $this->apiResponseError(500, ApiResponse::ERROR_SERVER_ERROR);
    }

    /**
     * Returns true if the request was for an API route
     *
     * @param \Illuminate\Http\Request $request
     * @return boolean
     */
    protected function isApi($request)
    {
        return $request->is('api/*');
    }

    /**
     * Returns an ApiResponse for an error response, with the HTTP status code, error code and
     * error message.
     *
     * @param int $status
     * @param string $code
     * @param string $message
     * @return \App\Api\Http\ApiResponse
     */
    protected function apiResponseError($status, $code, $message = null)
    {
        $apiResponse = new ApiResponse($status);
        $apiResponse->setError($code, $message);
        return $apiResponse;
    }
}
