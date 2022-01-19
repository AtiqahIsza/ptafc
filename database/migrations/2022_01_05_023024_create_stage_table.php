<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stage', function (Blueprint $table) {
            $table->id();
            $table->string('stage_name', 32);
            $table->string('stage_number', 8);
            $table->unsignedTinyInteger('stage_order')->nullable();
            $table->unsignedBigInteger('route_id')->nullable()->index('FK68AC2FE87F2551F');
            $table->string('no_of_km', 16)->nullable();

            $table->foreign(['route_id'], 'FK68AC2FE87F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->unique(['route_id', 'stage_order'], 'stage_order_idx');
            $table->unique(['stage_number', 'route_id'], 'stage_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stage');
    }
}
