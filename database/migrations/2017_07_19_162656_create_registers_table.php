<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->uuid('uuid');
            $table->smallInteger('state', false, false)->nullable();
            $table->string('employee', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->decimal('opening_cash', 8, 3)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 8, 3)->nullable();
            $table->string('post_ref', 30)->nullable();
            $table->decimal('post_amount', 8, 3)->nullable();
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
        Schema::dropIfExists('registers');
    }
}
