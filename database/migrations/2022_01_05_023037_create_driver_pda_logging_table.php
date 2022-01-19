<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverPdaLoggingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_pda_logging', function (Blueprint $table) {
            $table->id();
            $table->dateTime('sign_in')->nullable();
            $table->dateTime('sign_out')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FK3200E0767E2B6AE8');
            $table->unsignedBigInteger('driver_id')->nullable()->index('FK3200E076A8D29F52');
            $table->unsignedBigInteger('pda_id')->nullable()->index('FK3200E07653504BCC');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK3200E07687F2551F');
            $table->unsignedBigInteger('sector_id')->nullable()->index('FK3200E076EDF4A172');

            $table->foreign(['route_id'], 'FK3200E07687F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['sector_id'], 'FK3200E076EDF4A172')->references(['id'])->on('sector')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FK3200E0767E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['driver_id'], 'FK3200E076A8D29F52')->references(['id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['pda_id'], 'FK3200E07653504BCC')->references(['id'])->on('pda_profile')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_pda_logging');
    }
}
