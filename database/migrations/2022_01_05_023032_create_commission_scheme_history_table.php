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
            $table->id();
            $table->unsignedTinyInteger('agent_type')->nullable();
            $table->decimal('commission')->nullable();
            $table->unsignedInteger('from_value')->nullable();
            $table->dateTime('history_date')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedInteger('to_value')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index('FK93C169CE1F6798AB');
            $table->unsignedBigInteger('commission_scheme_id')->nullable()->index('FK93C169CEE2E9416');
            $table->unsignedBigInteger('user_id')->nullable()->index('FK93C169CEAF484C88');

            $table->foreign(['commission_scheme_id'], 'FK93C169CEE2E9416')->references(['id'])->on('commission_scheme')->onDelete('cascade');
            $table->foreign(['user_id'], 'FK93C169CEAF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK93C169CE1F6798AB')->references(['id'])->on('company')->onDelete('cascade');
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
