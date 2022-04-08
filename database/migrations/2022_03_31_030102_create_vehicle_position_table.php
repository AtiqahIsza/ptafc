<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclePositionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_position', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_reg_no', 16)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('imei', 40)->nullable();
            $table->string('altitude', 50)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->timestamps();
            $table->decimal('speed')->nullable();
            $table->decimal('bearing')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->unsignedInteger('satellite_count')->nullable();
            $table->decimal('hdop')->nullable();
            $table->unsignedInteger('d2d3')->nullable();
            $table->integer('rssi')->nullable();
            $table->integer('lac')->nullable();
            $table->unsignedBigInteger('cell_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_position');
    }
}
