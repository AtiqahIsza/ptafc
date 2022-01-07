<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->primary();
            $table->decimal('distance')->nullable();
            $table->string('route_name');
            $table->string('route_number', 8);
            $table->decimal('route_target')->nullable();
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FK67AB2491F6798AB');
            $table->unsignedBigInteger('sector_sector_id')->nullable()->index('FK67AB249EDF4A172');
            $table->unsignedTinyInteger('status')->nullable();
            $table->decimal('inbound_distance')->nullable()->default(0);
            $table->decimal('outbound_distance')->nullable()->default(0);

            $table->foreign(['sector_sector_id'], 'FK67AB249EDF4A172')->references(['sector_id'])->on('sector')->onDelete('cascade');
            $table->foreign(['company_company_id'], 'FK67AB2491F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->unique(['route_name', 'company_company_id'], 'route_name_idx');
            $table->unique(['route_number', 'company_company_id'], 'route_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route');
    }
}
