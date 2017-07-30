<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_products', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name', 200);
            $table->float('price');
            // Reference to the original product. If null, it means it is a custom product
            $table->integer('product_id')->unsigned()->nullable();
            $table->integer('item_id')->unsigned();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_products');
    }
}
