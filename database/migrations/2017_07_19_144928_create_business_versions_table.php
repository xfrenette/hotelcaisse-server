<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('created_at');
            $table->integer('business_id')->unsigned();
            $table->string('version', 64);
            // Comma separated list of attributes modified in this version
            $table->string('modifications', 255);

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
        Schema::dropIfExists('business_versions');
    }
}
