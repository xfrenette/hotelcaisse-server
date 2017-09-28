<?php

namespace App\Providers;

use App\Repositories\TeamRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Spark\Contracts\Repositories\TeamRepository as BaseTeamRepository;
use Spark;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->enhanceTeamCreation();
        $this->setUpBCMath();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * When creating a new Team through Spark, also creates a new Business and assign a slug.
     */
    protected function enhanceTeamCreation()
    {
        Spark::swap(BaseTeamRepository::class.'@create', TeamRepository::class.'@create');
    }

    /**
     * Default configuration for BCMath
     */
    protected function setUpBCMath()
    {
        bcscale(4);
    }
}
