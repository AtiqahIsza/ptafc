<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdaTrackingHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pda_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->string('altitude', 16)->nullable();
            $table->string('hist_id')->nullable()->unique('hist_id');
            $table->dateTime('history_date')->nullable();
            $table->string('in_border', 16)->nullable();
            $table->string('latitude', 16)->nullable();
            $table->string('longitude', 16)->nullable();
            $table->unsignedTinyInteger('sent_to_joompe')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->dateTime('upload_date')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FKCF230C1E7E2B6AE8');
            $table->unsignedBigInteger('pda_id')->nullable()->index('FKCF230C1E53504BCC');
            $table->unsignedBigInteger('route_id')->nullable()->index('FKCF230C1E87F2551F');
            $table->string('speed', 16)->nullable();
            $table->integer('bus_scheduler_id')->nullable();
            $table->string('trip_generated_id', 16)->nullable();

            $table->foreign(['route_id'], 'FKCF230C1E87F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FKCF230C1E7E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['pda_id'], 'FKCF230C1E53504BCC')->references(['id'])->on('pda_profile')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pda_tracking_history');
    }
}
