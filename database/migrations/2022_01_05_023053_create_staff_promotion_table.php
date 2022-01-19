<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffPromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_promotion', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('calculation_type')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedSmallInteger('from_amount')->nullable();
            $table->decimal('percentage')->nullable();
            $table->unsignedTinyInteger('promo_type')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->unsignedSmallInteger('to_amount')->nullable();
            $table->unsignedBigInteger('agent_company_id')->nullable()->index('FKF13AA204C6C7AD23');
            $table->unsignedBigInteger('company_id')->nullable()->index('FKF13AA2041F6798AB');

            $table->foreign(['agent_company_id'], 'FKF13AA204C6C7AD23')->references(['id'])->on('company')->onDelete('cascade');
            $table->foreign(['company_id'], 'FKF13AA2041F6798AB')->references(['id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_promotion');
    }
}
