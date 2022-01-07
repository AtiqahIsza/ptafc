<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketSalesTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_sales_transaction', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_id')->primary();
            $table->decimal('amount')->nullable();
            $table->unsignedTinyInteger('fare_type')->nullable();
            $table->string('pda_transaction_id', 32);
            $table->dateTime('sales_date');
            $table->dateTime('upload_date');
            $table->unsignedBigInteger('bus_bus_id')->nullable()->index('FKA7F171B87E2B6AE8');
            $table->bigInteger('busDriver_driver_id')->nullable()->index('FKA7F171B877071DD2');
            $table->unsignedBigInteger('card_card_id')->nullable()->index('FKA7F171B81483D948');
            $table->unsignedBigInteger('fromStage_stage_id')->nullable()->index('FKA7F171B884E9EE14');
            $table->unsignedBigInteger('route_route_id')->nullable()->index('FKA7F171B887F2551F');
            $table->unsignedBigInteger('sector_sector_id')->nullable()->index('FKA7F171B8EDF4A172');
            $table->unsignedBigInteger('toStage_stage_id')->nullable()->index('FKA7F171B862B52325');
            $table->unsignedBigInteger('summary_summary_id')->nullable()->index('FKA7F171B899B4A272');
            $table->unsignedBigInteger('pda_pda_id')->nullable()->index('FKA7F171B853504BCC');
            $table->decimal('balance_in_card')->nullable();
            $table->unsignedInteger('card_trx_sequence')->nullable();
            $table->string('trip_number', 8)->nullable();
            $table->string('ticket_number', 64)->nullable();
            $table->unsignedBigInteger('trip_trip_id')->nullable()->index('FKA7F171B8EC56E347');
            $table->decimal('actual_amount')->nullable();

            $table->foreign(['sector_sector_id'], 'FKA7F171B8EDF4A172')->references(['sector_id'])->on('sector')->onDelete('cascade');
            $table->foreign(['toStage_stage_id'], 'FKA7F171B862B52325')->references(['stage_id'])->on('stage')->onDelete('cascade');
            $table->foreign(['bus_bus_id'], 'FKA7F171B87E2B6AE8')->references(['bus_id'])->on('bus')->onDelete('cascade');
            $table->foreign(['route_route_id'], 'FKA7F171B887F2551F')->references(['route_id'])->on('route')->onDelete('cascade');
            $table->foreign(['trip_trip_id'], 'FKA7F171B8EC56E347')->references(['trip_id'])->on('trip_details')->onDelete('cascade');
            $table->foreign(['pda_pda_id'], 'FKA7F171B853504BCC')->references(['pda_id'])->on('pda_profile')->onDelete('cascade');
            $table->foreign(['busDriver_driver_id'], 'FKA7F171B877071DD2')->references(['driver_id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['fromStage_stage_id'], 'FKA7F171B884E9EE14')->references(['stage_id'])->on('stage')->onDelete('cascade');
            $table->foreign(['summary_summary_id'], 'FKA7F171B899B4A272')->references(['summary_id'])->on('ticket_transaction_summary')->onDelete('cascade');
            $table->foreign(['card_card_id'], 'FKA7F171B81483D948')->references(['card_id'])->on('ticket_card')->onDelete('cascade');
            $table->unique(['pda_transaction_id', 'sales_date'], 'pda_transaction_id_idx');
            $table->index(['route_route_id', 'sales_date', 'fare_type'], 'collection_by_route_and_type_idx');
            $table->index(['pda_pda_id', 'sales_date', 'amount'], 'pda_sales_total_idx');
            $table->index(['bus_bus_id', 'route_route_id', 'sales_date', 'fare_type'], 'collection_by_route_and_bus_and_type_idx');
            $table->index(['bus_bus_id', 'route_route_id', 'sales_date'], 'collection_by_route_and_bus_idx');
            $table->index(['route_route_id', 'sales_date'], 'collection_by_route_idx');
            $table->index(['pda_pda_id', 'sales_date'], 'pda_sales_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_sales_transaction');
    }
}
