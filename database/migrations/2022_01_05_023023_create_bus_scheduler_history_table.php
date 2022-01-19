<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusSchedulerHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_scheduler_history', function (Blueprint $table) {
            $table->id();
            $table->integer('no_of_kilometers')->nullable();
            $table->integer('no_of_trips')->nullable();
            $table->dateTime('schedule_date')->nullable();
            $table->integer('status')->nullable();
            $table->string('time')->nullable();
            $table->unsignedBigInteger('bus1_id')->nullable()->index('FK52C219BCAE94CE97');
            $table->unsignedBigInteger('bus2_id')->nullable()->index('FK52C219BC1675FB76');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK52C219BC87F2551F');
            $table->integer('trip_id')->nullable();
            $table->decimal('inbound_distance')->nullable()->default(0);
            $table->decimal('outbound_distance')->nullable()->default(0);
            $table->integer('trip_type')->nullable();

            $table->foreign(['bus1_id'], 'FK52C219BCAE94CE97')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['route_id'], 'FK52C219BC87F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['bus2_id'], 'FK52C219BC1675FB76')->references(['id'])->on('bus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_scheduler_history');
    }
}
