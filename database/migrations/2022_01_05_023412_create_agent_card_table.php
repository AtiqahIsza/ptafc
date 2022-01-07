<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_card', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_card_id')->primary();
            $table->dateTime('end_date');
            $table->dateTime('start_date');
            $table->unsignedBigInteger('agent_agent_id')->nullable()->index('FKB97C0C0A1EDF5D0A');
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FKB97C0C0A1483D948');

            $table->foreign(['agent_agent_id'], 'FKB97C0C0A1EDF5D0A')->references(['agent_id'])->on('reload_agent')->onDelete('cascade');
            $table->foreign(['card_card_id'], 'FKB97C0C0A1483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_card');
    }
}
