<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassengerBoardingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('passenger_boarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('in_count')->nullable();
            $table->unsignedInteger('out_count')->nullable();
            $table->string('sensor_status', 20)->nullable();
            $table->dateTime('transaction_time')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FKDFE4E1417E2B6AE8');

            $table->foreign(['bus_id'], 'FKDFE4E1417E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('passenger_boarding');
    }
}
