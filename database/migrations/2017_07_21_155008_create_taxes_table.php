<?php

use App\Tax;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name', 200);
            $table->float('amount', 8, 5);
            $table->enum('type', [Tax::TYPE_PERCENTAGE, Tax::TYPE_ABSOLUTE])->default(Tax::TYPE_PERCENTAGE);
            $table->boolean('applies_to_all')->default(true);
            $table->integer('business_id')->unsigned();

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
        Schema::dropIfExists('taxes');
    }
}
