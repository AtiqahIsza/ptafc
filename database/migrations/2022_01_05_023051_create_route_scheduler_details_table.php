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
            $table->dateTime('from_stage_time')->nullable();
            $table->dateTime('to_stage_timestamp')->nullable();
            $table->unsignedBigInteger('fromstage_stage_id')->nullable()->index('FK11BC6A4884E9EE14');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK11BC6A4887F2551F');
            $table->unsignedBigInteger('tostage_stage_id')->nullable()->index('FK11BC6A4862B52325');

            $table->foreign(['route_id'], 'FK11BC6A4887F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['fromstage_stage_id'], 'FK11BC6A4884E9EE14')->references(['id'])->on('stage')->onDelete('cascade');
            $table->foreign(['tostage_stage_id'], 'FK11BC6A4862B52325')->references(['id'])->on('stage')->onDelete('cascade');
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
