<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('end_trip');
            $table->string('first_ticket_number', 64)->nullable();
            $table->string('last_ticket_number', 64)->nullable();
            $table->bigInteger('number_of_pass')->nullable();
            $table->bigInteger('number_of_ticket')->nullable();
            $table->dateTime('start_trip');
            $table->decimal('total_collection')->nullable();
            $table->string('trip_number', 8)->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FKC3CF18287E2B6AE8');
            $table->unsignedBigInteger('driver_id')->nullable()->index('FKC3CF1828A8D29F52');
            $table->unsignedBigInteger('pda_id')->nullable()->index('FKC3CF182853504BCC');
            $table->unsignedBigInteger('route_id')->nullable()->index('FKC3CF182887F2551F');
            $table->string('trip_code', 64)->nullable();

            $table->foreign(['route_id'], 'FKC3CF182887F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FKC3CF18287E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['driver_id'], 'FKC3CF1828A8D29F52')->references(['id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['pda_id'], 'FKC3CF182853504BCC')->references(['id'])->on('pda_profile')->onDelete('cascade');
            $table->unique(['bus_id', 'driver_id', 'pda_id', 'route_id', 'start_trip', 'end_trip'], 'trip_details_dx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_details');
    }
}
