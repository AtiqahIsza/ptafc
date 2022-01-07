<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_map', function (Blueprint $table) {
            $table->unsignedBigInteger('map_id')->primary();
            $table->string('altitude', 16)->nullable();
            $table->string('latitude', 16);
            $table->string('longitude', 16);
            $table->unsignedSmallInteger('sequence')->nullable();
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FKA033A6687F2551F');

            $table->foreign(['route_route_id'], 'FKA033A6687F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_map');
    }
}
