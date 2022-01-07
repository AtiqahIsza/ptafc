<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_history', function (Blueprint $table) {
            $table->unsignedBigInteger('history_id')->primary();
            $table->dateTime('date_changed')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FKF16E7AF0AF484C88');

            $table->foreign(['user_user_id'], 'FKF16E7AF0AF484C88')->references(['id'])->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('password_history');
    }
}
