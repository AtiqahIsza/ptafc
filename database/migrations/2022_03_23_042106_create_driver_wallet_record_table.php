<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverWalletRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_wallet_record', function (Blueprint $table) {
            $table->id();
            $table->decimal('value')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable()->index('FK_driver_wallet_bus_driver');
            $table->unsignedBigInteger('created_by')->nullable()->index('FK_driver_wallet_users');
            $table->unsignedBigInteger('topup_promo_id')->nullable()->index('FK_driver_wallet_record_topup_promo');

            $table->foreign(['driver_id'], 'FK_driver_wallet_bus_driver')->references(['id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['created_by'], 'FK_driver_wallet_users')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['topup_promo_id'], 'FK_driver_wallet_record_topup_promo')->references(['id'])->on('topup_promo')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_wallet_record');
    }
}
