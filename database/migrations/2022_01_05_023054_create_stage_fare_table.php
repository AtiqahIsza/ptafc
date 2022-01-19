<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStageFareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stage_fare', function (Blueprint $table) {
            $table->id();
            $table->decimal('consession_fare')->nullable();
            $table->decimal('fare')->nullable();
            $table->unsignedBigInteger('fromstage_stage_id')->nullable()->index('FK42B7FCCF84E9EE14');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK42B7FCCF87F2551F');
            $table->unsignedBigInteger('tostage_stage_id')->nullable()->index('FK42B7FCCF62B52325');

            $table->foreign(['route_id'], 'FK42B7FCCF87F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['fromstage_stage_id'], 'FK42B7FCCF84E9EE14')->references(['id'])->on('stage')->onDelete('cascade');
            $table->foreign(['tostage_stage_id'], 'FK42B7FCCF62B52325')->references(['id'])->on('stage')->onDelete('cascade');
            $table->unique(['fromstage_stage_id', 'tostage_stage_id', 'route_id'], 'stage_fare_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stage_fare');
    }
}
