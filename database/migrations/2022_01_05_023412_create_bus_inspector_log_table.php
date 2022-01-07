<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusInspectorLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_inspector_log', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id')->primary();
            $table->dateTime('check_in_date');
            $table->dateTime('upload_date');
            $table->unsignedBigInteger('bus_bus_id')->nullable()->index('FK73C4AA7D7E2B6AE8');
            $table->bigInteger('driver_driver_id')->nullable()->index('FK73C4AA7DA8D29F52');
            $table->bigInteger('inspector_driver_id')->nullable()->index('FK73C4AA7DDE1C4B61');
            $table->unsignedBigInteger('inspectorCard_card_id')->nullable()->index('FK73C4AA7D158459BF');
            $table->unsignedBigInteger('pda_pda_id')->nullable()->index('FK73C4AA7D53504BCC');
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FK73C4AA7D87F2551F');
            $table->string('trip_number', 8)->nullable();

            $table->foreign(['bus_bus_id'], 'FK73C4AA7D7E2B6AE8')->references(['bus_id'])->on('bus')->onDelete('cascade');
            $table->foreign(['driver_driver_id'], 'FK73C4AA7DA8D29F52')->references(['driver_id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['pda_pda_id'], 'FK73C4AA7D53504BCC')->references(['pda_id'])->on('pda_profile')->onDelete('cascade');
            $table->foreign(['route_route_id'], 'FK73C4AA7D87F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
            $table->foreign(['inspector_driver_id'], 'FK73C4AA7DDE1C4B61')->references(['driver_id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['inspectorCard_card_id'], 'FK73C4AA7D158459BF')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_inspector_log');
    }
}
