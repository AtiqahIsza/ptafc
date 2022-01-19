<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLoginFailedLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_login_failed_log', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_attempt')->nullable();
            $table->string('location', 32)->nullable();
            $table->string('password');
            $table->string('username', 16);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_login_failed_log');
    }
}
