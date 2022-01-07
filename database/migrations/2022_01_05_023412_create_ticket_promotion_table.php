<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketPromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promo_id')->primary();
            $table->dateTime('end_date')->nullable();
            $table->decimal('promo')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->unsignedBigInteger('region_region_id')->nullable()->index('FK12F50330F26F9C4D');
            $table->unsignedTinyInteger('calculation_type')->nullable();
            $table->integer('from_amount')->nullable();
            $table->decimal('percentage')->nullable();
            $table->integer('to_amount')->nullable();

            $table->foreign(['region_region_id'], 'FK12F50330F26F9C4D')->references(['region_id'])->on('region_code')->onDelete('cascade');
            $table->unique(['region_region_id', 'start_date', 'end_date', 'from_amount', 'to_amount'], 'ticket_promo_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_promotion');
    }
}
