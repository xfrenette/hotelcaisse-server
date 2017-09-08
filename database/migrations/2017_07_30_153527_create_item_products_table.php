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
            $table->float('price', 11, 5);
            // Reference to the original product. If null, it means it is a custom product
            $table->integer('product_id')->unsigned()->nullable();

            $table->foreign('product_id')->references('id')->on('products');
        });

        // Update the 'items' table to reference this table
        Schema::table('items', function (Blueprint $table) {
            $table->integer('item_product_id')->unsigned();

            $table->foreign('item_product_id')->references('id')->on('item_products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['item_product_id']);
            $table->dropColumn('item_product_id');
        });

        Schema::dropIfExists('item_products');
    }
}
