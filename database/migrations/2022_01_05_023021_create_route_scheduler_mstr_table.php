<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusSchedulerMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_scheduler_mstr', function (Blueprint $table) {
            $table->id();
            $table->dateTime('schedule_start_time')->nullable();
            $table->dateTime('schedule_end_time')->nullable();
            $table->unsignedBigInteger('route_id')->nullable()->index('FK5E29F4787F2551F');
            $table->unsignedBigInteger('inbound_bus_id')->nullable()->index('FK_bus_scheduler_mstr_bus');
            $table->unsignedBigInteger('outbound_bus_id')->nullable()->index('FK_bus_scheduler_mstr_bus_2');
            $table->decimal('inbound_distance')->nullable()->default(0);
            $table->decimal('outbound_distance')->nullable()->default(0);
            $table->integer('status')->nullable();
            $table->integer('trip_type')->default(0);

            $table->foreign(['route_id'], 'FK5E29F4787F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['inbound_bus_id'], 'FK_bus_scheduler_mstr_bus')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['outbound_bus_id'], 'FK_bus_scheduler_mstr_bus_2')->references(['id'])->on('bus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_scheduler_mstr');
    }
}
