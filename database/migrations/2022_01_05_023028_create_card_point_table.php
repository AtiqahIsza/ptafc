<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardPointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_point', function (Blueprint $table) {
            $table->id();
            $table->decimal('credit_amount')->nullable();
            $table->decimal('debit_amount')->nullable();
            $table->dateTime('point_date');
            $table->unsignedTinyInteger('type')->nullable();
            $table->unsignedBigInteger('card_id')->nullable()->index('FK3B04A7A11483D948');

            $table->foreign(['card_id'], 'FK3B04A7A11483D948')->references(['id'])->on('ticket_card')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('card_point');
    }
}
