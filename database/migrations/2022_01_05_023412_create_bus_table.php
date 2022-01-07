<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus', function (Blueprint $table) {
            $table->unsignedBigInteger('bus_id')->primary();
            $table->string('bus_registration_number', 16);
            $table->string('bus_series_number', 32);
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FK17E801F6798AB');
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FK17E8087F2551F');
            $table->unsignedBigInteger('sector_sector_id')->nullable()->index('FK17E80EDF4A172');
            $table->string('bus_type')->nullable();
            $table->dateTime('bus_manfacturing_date')->nullable();
            $table->string('macAddress')->nullable();
            $table->string('bus_age')->nullable();
            $table->unsignedInteger('busType_id')->nullable()->index('FK17E8097E7CEA9');

            $table->foreign(['busType_id'], 'FK17E8097E7CEA9')->references(['id'])->on('bus_type')->onDelete('cascade');
            $table->foreign(['route_route_id'], 'FK17E8087F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
            $table->foreign(['sector_sector_id'], 'FK17E80EDF4A172')->references(['sector_id'])->on('sector')->onDelete('cascade');
            $table->foreign(['company_company_id'], 'FK17E801F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->unique(['bus_registration_number', 'company_company_id'], 'bus_reg_number_idx');
            $table->unique(['bus_series_number', 'company_company_id'], 'bus_ser_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus');
    }
}
