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
            $table->unsignedBigInteger('staff_card_id')->primary();
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FKF853D0F1483D948');
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FKF853D0F1F6798AB');

            $table->foreign(['company_company_id'], 'FKF853D0F1F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->foreign(['card_card_id'], 'FKF853D0F1483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
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
