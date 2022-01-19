<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardBlacklistHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_blacklist_history', function (Blueprint $table) {
            $table->id();
            $table->dateTime('blacklisted_date');
            $table->string('reason');
            $table->unsignedBigInteger('card_id')->nullable()->index('FK5B3A08031483D948');

            $table->foreign(['card_id'], 'FK5B3A08031483D948')->references(['id'])->on('ticket_card')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('card_blacklist_history');
    }
}
