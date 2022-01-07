<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionSchemeHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_scheme_history', function (Blueprint $table) {
            $table->unsignedBigInteger('history_id')->primary();
            $table->unsignedTinyInteger('agent_type')->nullable();
            $table->decimal('commission')->nullable();
            $table->unsignedInteger('from_value')->nullable();
            $table->dateTime('history_date')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedInteger('to_value')->nullable();
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FK93C169CE1F6798AB');
            $table->unsignedBigInteger('scheme_commission_id')->nullable()->index('FK93C169CEE2E9416');
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FK93C169CEAF484C88');

            $table->foreign(['scheme_commission_id'], 'FK93C169CEE2E9416')->references(['commission_id'])->on('commission_scheme')->onDelete('cascade');
            $table->foreign(['user_user_id'], 'FK93C169CEAF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['company_company_id'], 'FK93C169CE1F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_scheme_history');
    }
}
