<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppliedTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applied_taxes', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('amount', 11, 5);
            $table->enum('type', ['ItemProduct'])->default('ItemProduct');
            // If type='ItemProduct', references item_products->id
            $table->integer('instance_id')->unsigned();
            // Reference to the original Tax
            $table->integer('tax_id')->unsigned();

            $table->foreign('tax_id')->references('id')->on('taxes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applied_taxes');
    }
}
