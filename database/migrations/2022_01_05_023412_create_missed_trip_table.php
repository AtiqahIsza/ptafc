<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissedTripTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missed_trip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bus_age')->nullable();
            $table->string('bus_plate_number')->nullable();
            $table->string('direction', 10);
            $table->string('driver_id')->nullable();
            $table->string('od')->nullable();
            $table->string('route_no', 16);
            $table->dateTime('service_date')->nullable();
            $table->string('service_start_time')->nullable();
            $table->string('start_point')->nullable();
            $table->string('trip_no')->nullable();
            $table->integer('bus_type_id')->nullable();
            $table->bigInteger('route_route_id')->nullable();
            $table->integer('bus_scheduler_id')->nullable();
            $table->string('day_today', 2)->nullable();
            $table->bigInteger('bus_bus_id')->nullable();
            $table->integer('stage_stage_id')->nullable();
            $table->string('voc_km', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('missed_trip');
    }
}
