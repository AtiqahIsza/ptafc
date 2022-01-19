<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePassengerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('passenger_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('end_date');
            $table->dateTime('start_date');
            $table->unsignedBigInteger('card_id')->nullable()->index('FK37AEB39D1483D948');
            $table->string('address1', 128)->nullable();
            $table->string('address2', 128)->nullable();
            $table->string('cell_phone', 16)->nullable();
            $table->string('city', 64)->nullable();
            $table->dateTime('date_of_birth')->nullable();
            $table->unsignedTinyInteger('disabled')->nullable();
            $table->unsignedTinyInteger('elderly')->nullable();
            $table->string('email_address', 64)->nullable();
            $table->string('home_phone', 16)->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->string('nationality', 64)->nullable();
            $table->unsignedTinyInteger('orphan')->nullable();
            $table->unsignedTinyInteger('others')->nullable();
            $table->string('postcode', 8)->nullable();
            $table->unsignedTinyInteger('race')->nullable();
            $table->unsignedTinyInteger('sex')->nullable();
            $table->unsignedTinyInteger('single_mother')->nullable();
            $table->string('state', 64)->nullable();
            $table->unsignedTinyInteger('student')->nullable();
            $table->string('title', 16)->nullable();
            $table->unsignedTinyInteger('work')->nullable();
            $table->unsignedTinyInteger('bkk')->nullable();

            $table->foreign(['card_id'], 'FK37AEB39D1483D948')->references(['id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('passenger_details');
    }
}
