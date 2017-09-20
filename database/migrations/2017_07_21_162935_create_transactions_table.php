<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->uuid('uuid');
            $table->decimal('amount', 8, 3);
            $table->integer('register_id')->unsigned();
            $table->integer('transaction_mode_id')->unsigned();
            $table->integer('order_id')->unsigned();

            $table->foreign('register_id')->references('id')->on('registers');
            $table->foreign('transaction_mode_id')->references('id')->on('transaction_modes');
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
