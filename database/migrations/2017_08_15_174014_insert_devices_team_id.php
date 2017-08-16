<?php

use App\Device;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device now linked to Team instead of Business
 */
class InsertDevicesTeamId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');

            $table->integer('team_id')->unsigned();
            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Multi-step reverse
        // 1. Create the 'business_id' field (temporarily allow null value)
        Schema::table('devices', function (Blueprint $table) {
            $table->integer('business_id')->unsigned()->nullable();
        });

        // 2. For each Device, set the 'business_id' to the value of its Team->business_id
        $this->changeDeviceTeamToBusiness();

        // 3. Make the business_id a foreign key and make it non-nullable
        Schema::table('devices', function (Blueprint $table) {
            $table->integer('business_id')->unsigned()->change();
            $table->foreign('business_id')->references('id')->on('businesses');
            // 4. Delete team_id
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }

    protected function changeDeviceTeamToBusiness()
    {
        Device::with('team')->get()->each(function ($device) {
            $device->business_id = $device->team->business_id;
            $device->save();
        });
    }
}
