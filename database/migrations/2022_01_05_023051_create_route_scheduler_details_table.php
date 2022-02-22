<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteSchedulerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_scheduler_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('schedule_date')->nullable();
            $table->unsignedBigInteger('route_scheduler_mstr_id')->nullable()->index('FK_route_scheduler_details_route_scheduler_mstr');

            $table->foreign(['route_scheduler_mstr_id'], 'FK_route_scheduler_details_route_scheduler_mstr')->references(['id'])->on('route_scheduler_mstr')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_scheduler_details');
    }
}
