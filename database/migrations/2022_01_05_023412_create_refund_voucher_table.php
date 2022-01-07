<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundVoucherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refund_voucher', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_id')->primary();
            $table->decimal('amount')->nullable();
            $table->dateTime('date_claimed')->nullable();
            $table->dateTime('date_created');
            $table->string('voucher_number', 16);
            $table->unsignedBigInteger('agent_agent_id')->nullable()->index('FK60C51CE71EDF5D0A');
            $table->unsignedBigInteger('blacklistedCard_card_id')->nullable()->index('FK60C51CE7E28661C4');
            $table->unsignedBigInteger('claimCard_card_id')->nullable()->index('FK60C51CE7ADA30784');
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FK60C51CE7AF484C88');

            $table->foreign(['user_user_id'], 'FK60C51CE7AF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['claimCard_card_id'], 'FK60C51CE7ADA30784')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
            $table->foreign(['blacklistedCard_card_id'], 'FK60C51CE7E28661C4')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
            $table->foreign(['agent_agent_id'], 'FK60C51CE71EDF5D0A')->references(['agent_id'])->on('reload_agent')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refund_voucher');
    }
}
