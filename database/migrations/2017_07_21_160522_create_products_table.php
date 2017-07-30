<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            // For custom products created by the client. Null if created by the admin
            $table->uuid('uuid')->nullable();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->float('price');
            $table->boolean('is_custom')->default(false);
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('business_id')->unsigned();

            $table->foreign('parent_id')->references('id')->on('products');
            $table->foreign('business_id')->references('id')->on('businesses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}