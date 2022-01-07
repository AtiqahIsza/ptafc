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
            $table->unsignedBigInteger('scheduler_detail_id')->primary();
            $table->string('time')->nullable();
            $table->integer('trip_id')->nullable();
            $table->unsignedBigInteger('bus1_bus_id')->nullable()->index('FKEB4336BFAE94CE97');
            $table->unsignedBigInteger('bus2_bus_id')->nullable()->index('FKEB4336BF1675FB76');
            $table->unsignedBigInteger('scheduler_mstr_id')->index('FKEB4336BFFC086242');

            $table->foreign(['scheduler_mstr_id'], 'FKEB4336BFFC086242')->references(['schedule_id'])->on('bus_scheduler_mstr')->onDelete('cascade');
            $table->foreign(['bus1_bus_id'], 'FKEB4336BFAE94CE97')->references(['bus_id'])->on('bus')->onDelete('cascade');
            $table->foreign(['bus2_bus_id'], 'FKEB4336BF1675FB76')->references(['bus_id'])->on('bus')->onDelete('cascade');
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
