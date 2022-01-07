<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_card', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_card_id')->primary();
            $table->dateTime('end_date');
            $table->dateTime('start_date');
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FK4AAD74C71483D948');
            $table->bigInteger('driver_driver_id')->nullable()->index('FK4AAD74C7A8D29F52');

            $table->foreign(['driver_driver_id'], 'FK4AAD74C7A8D29F52')->references(['driver_id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['card_card_id'], 'FK4AAD74C71483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_card');
    }
}
