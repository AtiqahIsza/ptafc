<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSummaryGeneratorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('summary_generator', function (Blueprint $table) {
            $table->id();
            $table->decimal('farebox_collection', 10)->nullable();
            $table->decimal('incentive_amt', 10)->nullable();
            $table->string('month')->nullable();
            $table->unsignedInteger('ridership')->nullable();
            $table->string('year')->nullable();
            $table->unsignedBigInteger('route_id')->nullable()->index('FKA709657A87F2551F');

            $table->foreign(['route_id'], 'FKA709657A87F2551F')->references(['id'])->on('route')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('summary_generator');
    }
}
