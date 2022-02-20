<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusSchedulerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_scheduler_details', function (Blueprint $table) {
            $table->id();
            $table->string('time')->nullable();
            $table->integer('trip_id')->nullable();
            $table->unsignedBigInteger('bus1_id')->nullable()->index('FKEB4336BFAE94CE97');
            $table->unsignedBigInteger('bus2_id')->nullable()->index('FKEB4336BF1675FB76');
            $table->unsignedBigInteger('scheduler_mstr_id')->index('FKEB4336BFFC086242');
            $table->unsignedBigInteger('route_id')->index('FK_bus_scheduler_details_route');

            $table->foreign(['route_id'], 'FK_bus_scheduler_details_route')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['scheduler_mstr_id'], 'FKEB4336BFFC086242')->references(['id'])->on('bus_scheduler_mstr')->onDelete('cascade');
            $table->foreign(['bus1_id'], 'FKEB4336BFAE94CE97')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['bus2_id'], 'FKEB4336BF1675FB76')->references(['id'])->on('bus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_scheduler_details');
    }
}
