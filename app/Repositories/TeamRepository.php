<?php

namespace App\Repositories;

use App\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Spark\Spark;

class TeamRepository
{
    /**
     * Creates a new Team, assigns it a new Business and generates its slug if not already in $data.
     * Swap for Laravel\Spark\Repositories\TeamRepository@create.
     *
     * @param \App\User $user
     * @param array $data
     *
     * @return \App\Team
     */
    public function create($user, array $data)
    {
        $team = null;

        // We don't want an orphaned Business in case the Team cannot be created, so we use a transaction
        DB::transaction(function () use ($user, $data, &$team) {
            // New: we create a new Business that will be associated to the Team
            $business = new Business();
            $business->save();

            // Same as swapped method:
            $attributes = [
                'owner_id' => $user->id,
                'name' => $data['name'],
                'trial_ends_at' => Carbon::now()->addDays(Spark::teamTrialDays()),
            ];

            // New: we always make sure the team has a slug
            $slug = array_key_exists('slug', $data) ? $data['slug'] : str_slug($data['name']);
            $attributes['slug'] = $slug;

            $team = Spark::team();
            $team->forceFill($attributes);
            $team->business()->associate($business);
            $team->save();
        });

        return $team;
    }
}
