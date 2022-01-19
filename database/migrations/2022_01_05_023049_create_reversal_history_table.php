<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReversalHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reversal_history', function (Blueprint $table) {
            $table->id();
            $table->string('remarks');
            $table->dateTime('reversal_date');
            $table->unsignedBigInteger('credit_reversal_transaction_id')->nullable()->index('FK4CCC66A33D71ABCD');
            $table->unsignedBigInteger('creditted_transaction_id')->nullable()->index('FK4CCC66A3E72243A');
            $table->unsignedBigInteger('debit_reversal_transaction_id')->nullable()->index('FK4CCC66A3146B407A');
            $table->unsignedBigInteger('debitted_transaction_id')->nullable()->index('FK4CCC66A371EF976D');
            $table->unsignedBigInteger('user_id')->nullable()->index('FK4CCC66A3AF484C88');

            $table->foreign(['debitted_transaction_id'], 'FK4CCC66A371EF976D')->references(['id'])->on('agent_account')->onDelete('cascade');
            $table->foreign(['creditted_transaction_id'], 'FK4CCC66A3E72243A')->references(['id'])->on('agent_account')->onDelete('cascade');
            $table->foreign(['credit_reversal_transaction_id'], 'FK4CCC66A33D71ABCD')->references(['id'])->on('agent_account')->onDelete('cascade');
            $table->foreign(['user_id'], 'FK4CCC66A3AF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['debit_reversal_transaction_id'], 'FK4CCC66A3146B407A')->references(['id'])->on('agent_account')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reversal_history');
    }
}
