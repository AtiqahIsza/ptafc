<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentStaffTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_staff_transaction', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->primary();
            $table->dateTime('transaction_date')->nullable();
            $table->unsignedBigInteger('referenceId_transaction_id')->nullable()->index('FKBF7DF945B51EC78E');
            $table->unsignedBigInteger('staff_staff_id')->nullable()->index('FKBF7DF945FB610EC5');

            $table->foreign(['staff_staff_id'], 'FKBF7DF945FB610EC5')->references(['staff_id'])->on('agent_staff')->onDelete('cascade');
            $table->foreign(['referenceId_transaction_id'], 'FKBF7DF945B51EC78E')->references(['transaction_id'])->on('agent_account')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_staff_transaction');
    }
}
