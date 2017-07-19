<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_approvals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('passcode', 255);
            $table->timestamps();
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
        Schema::dropIfExists('device_approvals');
    }
}
