<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStageMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stage_map', function (Blueprint $table) {
            $table->unsignedBigInteger('map_id')->primary();
            $table->string('altitude', 16)->nullable();
            $table->string('latitude', 16);
            $table->string('longitude', 16);
            $table->unsignedSmallInteger('sequence')->nullable();
            $table->unsignedBigInteger('stage_stage_id')->nullable()->index('FK6D81E89B367987EA');

            $table->foreign(['stage_stage_id'], 'FK6D81E89B367987EA')->references(['stage_id'])->on('stage')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stage_map');
    }
}
