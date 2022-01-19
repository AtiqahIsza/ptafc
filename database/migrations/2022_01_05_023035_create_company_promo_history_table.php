<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyPromoHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_promo_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('calculation_type')->nullable();
            $table->integer('from_amount')->nullable();
            $table->dateTime('from_date')->nullable();
            $table->dateTime('history_date')->nullable();
            $table->decimal('percentage')->nullable();
            $table->integer('status');
            $table->integer('to_amount')->nullable();
            $table->dateTime('to_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index('FKD17C5221F6798AB');
            $table->unsignedBigInteger('promo_id')->nullable()->index('FKD17C5227D33FC5E');
            $table->unsignedBigInteger('user_id')->nullable()->index('FKD17C522AF484C88');

            $table->foreign(['user_id'], 'FKD17C522AF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['promo_id'], 'FKD17C5227D33FC5E')->references(['id'])->on('company_promo')->onDelete('cascade');
            $table->foreign(['company_id'], 'FKD17C5221F6798AB')->references(['id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_promo_history');
    }
}
