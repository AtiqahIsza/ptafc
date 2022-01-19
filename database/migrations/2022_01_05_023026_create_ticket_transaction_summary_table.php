<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketTransactionSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_transaction_summary', function (Blueprint $table) {
            $table->id();
            $table->string('batch_filename', 64)->unique('batch_file_idx');
            $table->dateTime('end_date');
            $table->unsignedTinyInteger('number_of_trip')->nullable();
            $table->dateTime('start_date');
            $table->decimal('total_collection')->nullable();
            $table->dateTime('upload_date');
            $table->unsignedBigInteger('bus_id')->nullable()->index('FKF69C5D927E2B6AE8');
            $table->unsignedBigInteger('route_id')->nullable()->index('FKF69C5D9287F2551F');

            $table->foreign(['route_id'], 'FKF69C5D9287F2551F')->references(['id'])->on('route')->onDelete('cascade');
            $table->foreign(['bus_id'], 'FKF69C5D927E2B6AE8')->references(['id'])->on('bus')->onDelete('cascade');
            $table->index(['bus_id', 'route_id', 'start_date', 'end_date'], 'summary_by_route_and_bus_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_transaction_summary');
    }
}
