<?php

namespace App\Providers;

use App\Api\Auth\ApiAuth;
use Illuminate\Support\ServiceProvider;

class ApiAuthServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('apiauth', function () {
            return new ApiAuth(config('api.auth'));
        });
    }
}
