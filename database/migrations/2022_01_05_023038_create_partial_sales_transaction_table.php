<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartialSalesTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partial_sales_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('altitude', 16)->nullable();
            $table->decimal('balance_in_card')->nullable();
            $table->bigInteger('bus_stand_id')->nullable();
            $table->string('card_number');
            $table->unsignedInteger('card_trx_sequence')->nullable();
            $table->decimal('fare')->nullable();
            $table->unsignedTinyInteger('fare_type')->nullable();
            $table->string('latitude', 16)->nullable();
            $table->string('longitude', 16)->nullable();
            $table->decimal('paid_amount')->nullable();
            $table->string('ticket_number');
            $table->dateTime('transaction_date')->nullable();
            $table->unsignedTinyInteger('transaction_type')->nullable();
            $table->string('trip_generated_id', 20)->nullable();
            $table->string('trip_number', 8)->nullable();
            $table->dateTime('upload_date')->nullable();
            $table->unsignedBigInteger('bus_id')->nullable()->index('FK2E88FCED7E2B6AE8');
            $table->unsignedBigInteger('driver_id')->nullable()->index('FK2E88FCEDA8D29F52');
            $table->unsignedBigInteger('parent_transaction_id')->nullable()->index('FK2E88FCEDA23C133');
            $table->unsignedBigInteger('pda_id')->nullable()->index('FK2E88FCED53504BCC');
            $table->unsignedBigInteger('route_id')->nullable()->index('FK2E88FCED87F2551F');
            $table->unsignedBigInteger('sector_id')->nullable()->index('FK2E88FCEDEDF4A172');
            $table->unsignedBigInteger('stage_id')->nullable()->index('FK2E88FCED367987EA');
            $table->unsignedBigInteger('trip_id')->nullable()->index('FK2E88FCEDEC56E347');

            $table->foreign(['bus_id'], 'FK2E88FCED7E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['parent_transaction_id'], 'FK2E88FCEDA23C133')->references(['id'])->on('partial_sales_transaction')->onDelete('cascade');
            $table->foreign(['trip_id'], 'FK2E88FCEDEC56E347')->references(['id'])->on('trip_details')->onDelete('cascade');
            $table->foreign(['pda_id'], 'FK2E88FCED53504BCC')->references(['id'])->on('pda_profile')->onDelete('cascade');
            $table->foreign(['route_id'], 'FK2E88FCED87F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['driver_id'], 'FK2E88FCEDA8D29F52')->references(['id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['sector_id'], 'FK2E88FCEDEDF4A172')->references(['id'])->on('sector')->onDelete('cascade');
            $table->foreign(['stage_id'], 'FK2E88FCED367987EA')->references(['id'])->on('stage')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partial_sales_transaction');
    }
}
