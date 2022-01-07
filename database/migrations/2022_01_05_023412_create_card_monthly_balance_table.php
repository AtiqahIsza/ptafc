<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardMonthlyBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_monthly_balance', function (Blueprint $table) {
            $table->unsignedBigInteger('balance_id')->primary();
            $table->decimal('balance')->nullable();
            $table->decimal('balance_carried_forward')->nullable();
            $table->unsignedTinyInteger('balance_month')->nullable();
            $table->unsignedSmallInteger('balance_year')->nullable();
            $table->decimal('credit_amount')->nullable();
            $table->decimal('debit_amount')->nullable();
            $table->decimal('ticket_total')->nullable();
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FK848EF97B1483D948');

            $table->foreign(['card_card_id'], 'FK848EF97B1483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('card_monthly_balance');
    }
}
