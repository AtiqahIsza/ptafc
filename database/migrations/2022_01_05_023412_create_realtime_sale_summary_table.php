<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealtimeSaleSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realtime_sale_summary', function (Blueprint $table) {
            $table->unsignedBigInteger('realtime_id')->primary();
            $table->bigInteger('bus_id')->nullable();
            $table->bigInteger('bus_scheduler_id')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->bigInteger('driver_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->integer('no_of_pax_adult')->nullable();
            $table->integer('no_of_pax_child')->nullable();
            $table->bigInteger('route_id')->nullable();
            $table->bigInteger('stage_id')->nullable();
            $table->double('total_collection_adult')->nullable();
            $table->double('total_collection_child')->nullable();
            $table->double('speed')->nullable();
            $table->integer('no_of_pax_concession')->nullable();
            $table->bigInteger('total_collection_concession')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('realtime_sale_summary');
    }
}
