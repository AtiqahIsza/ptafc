<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_card', function (Blueprint $table) {
            $table->id();
            $table->string('card_holder_name', 128)->nullable();
            $table->string('card_number', 64)->unique('card_number_idx');
            $table->unsignedTinyInteger('card_status')->nullable();
            $table->unsignedSmallInteger('card_type')->nullable();
            $table->decimal('current_balance', 11)->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->string('id_number', 16)->nullable();
            $table->string('manufacturing_id', 64)->unique('manufacturing_id_idx');
            $table->decimal('point_balance', 11)->nullable();
            $table->unsignedBigInteger('createdBy_agent_id')->nullable()->index('FK1585D063C3139010');
            $table->unsignedBigInteger('region_id')->nullable()->index('FK1585D063F26F9C4D');

            $table->foreign(['region_id'], 'FK1585D063F26F9C4D')->references(['id'])->on('region_code')->onDelete('cascade');
            $table->foreign(['createdby_agent_id'], 'FK1585D063C3139010')->references(['id'])->on('reload_agent')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_card');
    }
}
