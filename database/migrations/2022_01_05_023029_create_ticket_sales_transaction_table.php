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
            $table->id();
            $table->decimal('amount')->nullable();
            $table->unsignedTinyInteger('fare_type')->nullable();
            $table->string('pda_transaction_id', 32);
            $table->dateTime('sales_date');
            $table->dateTime('upload_date');
            $table->unsignedBigInteger('bus_id')->nullable()->index('FKA7F171B87E2B6AE8');
            $table->unsignedBigInteger('bus_driver_id')->nullable()->index('FKA7F171B877071DD2');
            $table->unsignedBigInteger('card_id')->nullable()->index('FKA7F171B81483D948');
            $table->unsignedBigInteger('fromstage_stage_id')->nullable()->index('FKA7F171B884E9EE14');
            $table->unsignedBigInteger('route_id')->nullable()->index('FKA7F171B887F2551F');
            $table->unsignedBigInteger('sector_id')->nullable()->index('FKA7F171B8EDF4A172');
            $table->unsignedBigInteger('tostage_stage_id')->nullable()->index('FKA7F171B862B52325');
            $table->unsignedBigInteger('summary_id')->nullable()->index('FKA7F171B899B4A272');
            $table->unsignedBigInteger('pda_id')->nullable()->index('FKA7F171B853504BCC');
            $table->decimal('balance_in_card')->nullable();
            $table->unsignedInteger('card_trx_sequence')->nullable();
            $table->string('trip_number', 8)->nullable();
            $table->string('ticket_number', 64)->nullable();
            $table->unsignedBigInteger('trip_id')->nullable()->index('FKA7F171B8EC56E347');
            $table->decimal('actual_amount')->nullable();
            $table->unsignedTinyInteger('passenger_type')->nullable();
            $table->unsignedBigInteger('bus_stand_in_id')->nullable()->index('FK_ticket_sales_transaction_bus_stand');
            $table->unsignedBigInteger('bus_stand_out_id')->nullable()->index('FK_ticket_sales_transaction_bus_stand_2');

            $table->foreign(['sector_id'], 'FKA7F171B8EDF4A172')->references(['id'])->on('sector')->onDelete('cascade');
            $table->foreign(['tostage_stage_id'], 'FKA7F171B862B52325')->references(['id'])->on('stage')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FKA7F171B87E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->foreign(['route_id'], 'FKA7F171B887F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['trip_id'], 'FKA7F171B8EC56E347')->references(['id'])->on('trip_details')->onDelete('cascade');
            $table->foreign(['pda_id'], 'FKA7F171B853504BCC')->references(['id'])->on('pda_profile')->onDelete('cascade');
            $table->foreign(['bus_driver_id'], 'FKA7F171B877071DD2')->references(['id'])->on('bus_driver')->onDelete('cascade');
            $table->foreign(['fromstage_stage_id'], 'FKA7F171B884E9EE14')->references(['id'])->on('stage')->onDelete('cascade');
            $table->foreign(['summary_id'], 'FKA7F171B899B4A272')->references(['id'])->on('ticket_transaction_summary')->onDelete('cascade');
            $table->foreign(['card_id'], 'FKA7F171B81483D948')->references(['id'])->on('ticket_card')->onDelete('cascade');
            $table->foreign(['bus_stand_in_id'], 'FK_ticket_sales_transaction_bus_stand')->references(['id'])->on('bus_stand')->onDelete('cascade');
            $table->foreign(['bus_stand_out_id'], 'FK_ticket_sales_transaction_bus_stand_2')->references(['id'])->on('bus_stand')->onDelete('cascade');

            $table->unique(['pda_transaction_id', 'sales_date'], 'pda_transaction_id_idx');
            $table->index(['route_id', 'sales_date', 'fare_type'], 'collection_by_route_and_type_idx');
            $table->index(['pda_id', 'sales_date', 'amount'], 'pda_sales_total_idx');
            $table->index(['bus_id', 'route_id', 'sales_date', 'fare_type'], 'collection_by_route_and_bus_and_type_idx');
            $table->index(['bus_id', 'route_id', 'sales_date'], 'collection_by_route_and_bus_idx');
            $table->index(['route_id', 'sales_date'], 'collection_by_route_idx');
            $table->index(['pda_id', 'sales_date'], 'pda_sales_idx');
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
