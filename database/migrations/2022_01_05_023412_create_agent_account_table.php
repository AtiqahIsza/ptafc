<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_account', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->primary();
            $table->decimal('credit_amount')->nullable();
            $table->decimal('debit_amount')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->string('transaction_info', 32)->nullable();
            $table->unsignedBigInteger('agent_agent_id')->nullable()->index('FKA34D9EF31EDF5D0A');
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FKA34D9EF31483D948');
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FKA34D9EF31F6798AB');
            $table->unsignedBigInteger('parent_transaction_id')->nullable()->index('FKA34D9EF3A6517C0A');
            $table->unsignedBigInteger('referenceId_transaction_id')->nullable()->index('FKA34D9EF3B51EC78E');
            $table->unsignedSmallInteger('type_type_id')->nullable()->index('FKA34D9EF359AC8697');
            $table->string('transaction_key', 64)->nullable();
            $table->unsignedTinyInteger('trx_status')->nullable();
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FKA34D9EF3AF484C88');

            $table->foreign(['parent_transaction_id'], 'FKA34D9EF3A6517C0A')->references(['transaction_id'])->on('agent_account')->onDelete('cascade');
            $table->foreign(['referenceId_transaction_id'], 'FKA34D9EF3B51EC78E')->references(['transaction_id'])->on('agent_account')->onDelete('cascade');
            $table->foreign(['agent_agent_id'], 'FKA34D9EF31EDF5D0A')->references(['agent_id'])->on('reload_agent')->onDelete('cascade');
            $table->foreign(['type_type_id'], 'FKA34D9EF359AC8697')->references(['type_id'])->on('account_transaction_type')->onDelete('cascade');
            $table->foreign(['user_user_id'], 'FKA34D9EF3AF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['card_card_id'], 'FKA34D9EF31483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
            $table->foreign(['company_company_id'], 'FKA34D9EF31F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_account');
    }
}
