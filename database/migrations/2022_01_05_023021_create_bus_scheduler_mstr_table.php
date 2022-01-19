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
            $table->dateTime('schedule_date')->nullable();
            $table->integer('no_of_kilometers')->nullable();
            $table->integer('no_of_trips')->nullable();
            $table->unsignedBigInteger('route_id')->nullable()->index('FK5E29F4787F2551F');
            $table->decimal('inbound_distance')->nullable()->default(0);
            $table->decimal('outbound_distance')->nullable()->default(0);
            $table->integer('status')->nullable();
            $table->integer('trip_type')->default(0);

            $table->foreign(['route_id'], 'FK5E29F4787F2551F')->references(['id'])->on('route')->onDelete('cascade');
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
