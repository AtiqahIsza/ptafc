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
            $table->bigInteger('logging_id')->primary();
            $table->dateTime('sign_in')->nullable();
            $table->dateTime('sign_out')->nullable();
            $table->unsignedBigInteger('bus_bus_id')->nullable()->index('FK3200E0767E2B6AE8');
            $table->bigInteger('driver_driver_id')->nullable()->index('FK3200E076A8D29F52');
            $table->unsignedBigInteger('pda_pda_id')->nullable()->index('FK3200E07653504BCC');
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FK3200E07687F2551F');
            $table->unsignedBigInteger('sector_sector_id')->nullable()->index('FK3200E076EDF4A172');

            $table->foreign(['route_route_id'], 'FK3200E07687F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
            $table->foreign(['sector_sector_id'], 'FK3200E076EDF4A172')->references(['sector_id'])->on('sector')->onDelete('cascade');
            $table->foreign(['bus_bus_id'], 'FK3200E0767E2B6AE8')->references(['bus_id'])->on('bus')->onDelete('cascade');
            $table->foreign(['driver_driver_id'], 'FK3200E076A8D29F52')->references(['driver_id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['pda_pda_id'], 'FK3200E07653504BCC')->references(['pda_id'])->on('pda_profile')->onDelete('cascade');
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
