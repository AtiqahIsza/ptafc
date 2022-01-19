<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_promo', function (Blueprint $table) {
            $table->id();
            $table->integer('from_amount')->nullable();
            $table->dateTime('from_date')->nullable();
            $table->decimal('percentage')->nullable();
            $table->integer('to_amount')->nullable();
            $table->dateTime('to_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index('FK523156CD1F6798AB');
            $table->unsignedTinyInteger('calculation_type')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index('FK523156CDAF484C88');

            $table->foreign(['user_id'], 'FK523156CDAF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK523156CD1F6798AB')->references(['id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_promo');
    }
}
