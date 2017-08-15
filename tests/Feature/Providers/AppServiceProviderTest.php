<?php

namespace Tests\Feature\Providers;

use App\Business;
use App\User;
use Laravel\Spark\Contracts\Interactions\Settings\Teams\CreateTeam;
use Laravel\Spark\Spark;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function testCreateTeamCreatesBusinessAndSlug()
    {
        $user = factory(User::class)->create();
        $data = [
            'name' => 'Test Team',
        ];

        $team = Spark::interact(CreateTeam::class, [$user, $data]);

        $this->assertNotNull($team->business);
        $this->assertInstanceOf(Business::class, $team->business);
        $this->assertNotNull($team->slug);
    }
}
