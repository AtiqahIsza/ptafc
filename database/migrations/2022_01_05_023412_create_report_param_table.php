<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportParamTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_param', function (Blueprint $table) {
            $table->string('report_name', 64)->nullable();
            $table->string('report_parameter', 64)->nullable();

            $table->foreign(['report_name'], 'report_param_ibfk_1')->references(['report_name'])->on('report')->onDelete('cascade');
            $table->unique(['report_name', 'report_parameter'], 'report_param_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_param');
    }
}
