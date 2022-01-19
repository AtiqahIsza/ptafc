<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_card', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id')->nullable()->index('FKF853D0F1483D948');
            $table->unsignedBigInteger('company_id')->nullable()->index('FKF853D0F1F6798AB');

            $table->foreign(['company_id'], 'FKF853D0F1F6798AB')->references(['id'])->on('company')->onDelete('cascade');
            $table->foreign(['card_id'], 'FKF853D0F1483D948')->references(['id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_card');
    }
}
