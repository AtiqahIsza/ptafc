<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusDriverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_driver', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name', 128);
            $table->string('driver_number', 16);
            $table->string('driver_password');
            $table->unsignedTinyInteger('driver_role')->nullable();
            $table->string('employee_number', 32);
            $table->string('id_number', 16)->nullable();
            $table->decimal('target_collection')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FK4417C6C77E2B6AE8');
            $table->unsignedBigInteger('company_id')->nullable()->index('FK4417C6C71F6798AB');
            $table->unsignedBigInteger('hunter_id')->nullable()->index('FK4417C6C7A4A23854');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK4417C6C787F2551F');
            $table->unsignedBigInteger('sector_id')->nullable()->index('FK4417C6C7EDF4A172');
            $table->unsignedTinyInteger('status')->nullable();

            $table->foreign(['route_id'], 'FK4417C6C787F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['sector_id'], 'FK4417C6C7EDF4A172')->references(['id'])->on('sector')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FK4417C6C77E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['hunter_id'], 'FK4417C6C7A4A23854')->references(['id'])->on('driver_hunter')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK4417C6C71F6798AB')->references(['id'])->on('company')->onDelete('cascade');
            $table->unique(['driver_number', 'company_id'], 'driver_number_idx');
            $table->unique(['employee_number', 'company_id'], 'employee_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_driver');
    }
}
