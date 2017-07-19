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
            $table->uuid('uuid')->unique();
            $table->smallInteger('state', false, false);
            $table->string('employee', 100)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->float('opening_cash')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->float('closing_cash')->nullable();
            $table->string('post_ref', 30)->nullable();
            $table->float('post_amount')->nullable();
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
