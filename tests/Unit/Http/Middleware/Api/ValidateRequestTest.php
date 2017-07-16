<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Exceptions\Api\InvalidRequestException;
use App\Http\Middleware\Api\ValidateRequest;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateRequestTest extends TestCase
{
    /**
     * @var ValidateRequest
     */
    protected $middleware;

    protected function setUp()
    {
        $this->middleware = new ValidateRequest();
        parent::setUp();
    }

    public function testThrowsIfNotJSONRequest()
    {
        $request = Request::create('test');
        $request->setJson(['a' => 'b']);
        $this->expectException(InvalidRequestException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfEmptyBody()
    {
        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );
        $this->expectException(InvalidRequestException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfInvalidJSON()
    {
        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid-json'
        );
        $this->expectException(InvalidRequestException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testDoesNotThrowIfValidJson()
    {
        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"test":"value"}'
        );
        // Should no throw any exception and thus the test should succeed
        $this->middleware->handle($request, function () {
            //
        });
    }
}
