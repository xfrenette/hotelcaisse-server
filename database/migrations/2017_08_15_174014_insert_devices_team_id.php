<?php

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
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');

            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('businesses');
        });
    }
}
