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
            $table->id();
            $table->string('bus_registration_number', 16);
            $table->string('bus_series_number', 32);
            $table->unsignedBigInteger('company_id')->nullable()->index('FK17E801F6798AB');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK17E8087F2551F');
            $table->unsignedBigInteger('sector_id')->nullable()->index('FK17E80EDF4A172');
            $table->string('bus_type')->nullable();
            $table->dateTime('bus_manufacturing_date')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('bus_age')->nullable();
            $table->unsignedBigInteger('bus_type_id')->nullable()->index('FK17E8097E7CEA9');

            $table->foreign(['bus_type_id'], 'FK17E8097E7CEA9')->references(['id'])->on('bus_type')->onDelete('cascade');
            $table->foreign(['route_id'], 'FK17E8087F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['sector_id'], 'FK17E80EDF4A172')->references(['id'])->on('sector')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK17E801F6798AB')->references(['id'])->on('company')->onDelete('cascade');
            $table->unique(['bus_registration_number', 'company_id'], 'bus_reg_number_idx');
            $table->unique(['bus_series_number', 'company_id'], 'bus_ser_number_idx');
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
