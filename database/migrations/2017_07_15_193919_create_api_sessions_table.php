<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->string('token', 64)->unique();
            $table->dateTime('expires_at');
            $table->integer('device_id')->unsigned();

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
