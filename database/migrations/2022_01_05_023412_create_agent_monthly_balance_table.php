<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentMonthlyBalanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_monthly_balance', function (Blueprint $table) {
            $table->unsignedBigInteger('balance_id')->primary();
            $table->decimal('balance')->nullable();
            $table->decimal('balance_carried_forward')->nullable();
            $table->unsignedTinyInteger('balance_month')->nullable();
            $table->unsignedSmallInteger('balance_year')->nullable();
            $table->decimal('credit_amount')->nullable();
            $table->decimal('debit_amount')->nullable();
            $table->unsignedBigInteger('agent_agent_id')->nullable()->index('FK180B37701EDF5D0A');

            $table->foreign(['agent_agent_id'], 'FK180B37701EDF5D0A')->references(['agent_id'])->on('reload_agent')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_monthly_balance');
    }
}
