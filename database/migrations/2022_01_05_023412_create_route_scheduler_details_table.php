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
            $table->unsignedBigInteger('route_schd_id')->primary();
            $table->dateTime('from_stage_time')->nullable();
            $table->dateTime('to_stage_timestamp')->nullable();
            $table->unsignedBigInteger('fromStage_stage_id')->nullable()->index('FK11BC6A4884E9EE14');
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FK11BC6A4887F2551F');
            $table->unsignedBigInteger('toStage_stage_id')->nullable()->index('FK11BC6A4862B52325');

            $table->foreign(['route_route_id'], 'FK11BC6A4887F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
            $table->foreign(['fromStage_stage_id'], 'FK11BC6A4884E9EE14')->references(['stage_id'])->on('stage')->onDelete('cascade');
            $table->foreign(['toStage_stage_id'], 'FK11BC6A4862B52325')->references(['stage_id'])->on('stage')->onDelete('cascade');
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
