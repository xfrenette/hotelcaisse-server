<?php

namespace Tests;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteNameTest extends TestCase {
    public function testRouteName()
    {
        // The following works :
        // Route::name('test.test')
        //     ->get('/test', function() {
        //         return 'Hello world!';
        //    });

        // But this does not work :
        Route::get('/test', function() {
            return 'Hello world!';
        })->name('test.test');

        $uri = route('test.test');
        $response = $this->get($uri);
        $response->assertSee('Hello world!');
    }
}