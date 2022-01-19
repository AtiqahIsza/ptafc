<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoardingArrivalMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('boarding_arrival_map', function (Blueprint $table) {
            $table->id();
            $table->string('latitude', 16);
            $table->string('longitude', 16);
            $table->integer('stage_order_id');
            $table->unsignedBigInteger('route_id')->nullable()->index('FKF99EEF9387F2551F');

            $table->foreign(['route_id'], 'FKF99EEF9387F2551F')->references(['id'])->on('route')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('boarding_arrival_map');
    }
}
