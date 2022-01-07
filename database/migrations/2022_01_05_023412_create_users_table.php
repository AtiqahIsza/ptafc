<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->primary();
            $table->dateTime('created_at')->nullable();
            $table->string('full_name', 128);
            $table->string('ic_number', 12);
            $table->string('password');
            $table->string('phone_number', 16);
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedTinyInteger('user_role')->nullable();
            $table->string('username', 16)->unique('username_idx');
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FK487E21351F6798AB');
            $table->unsignedTinyInteger('failed_attempt')->nullable();
            $table->string('session_id')->nullable();

            $table->foreign(['company_company_id'], 'FK487E21351F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->unique(['ic_number', 'company_company_id'], 'user_ic_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profile');
    }
}
