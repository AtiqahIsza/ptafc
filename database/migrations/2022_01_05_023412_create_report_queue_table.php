<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('queue_id')->primary();
            $table->dateTime('date_completed')->nullable();
            $table->dateTime('date_requested');
            $table->string('parameter_values', 655);
            $table->string('report_link', 655)->nullable();
            $table->string('report_name', 88);
            $table->integer('report_status');
            $table->string('report_type', 16);
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FK38481A46AF484C88');

            $table->foreign(['user_user_id'], 'FK38481A46AF484C88')->references(['id'])->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_queue');
    }
}
