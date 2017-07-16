<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->uuid('token')->unique();
            $table->dateTime('expires_at');
            $table->integer('business_id')->unsigned();
            $table->integer('device_id')->unsigned();

            $table->foreign('business_id')->references('id')->on('businesses');
            $table->foreign('device_id')->references('id')->on('devices');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_sessions');
    }
}
