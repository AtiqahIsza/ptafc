<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdaLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pda_log', function (Blueprint $table) {
            $table->unsignedBigInteger('pda_log_id')->primary();
            $table->string('data', 1280)->nullable();
            $table->dateTime('date_upload');
            $table->unsignedBigInteger('pda_pda_id')->nullable()->index('FKD4E244F253504BCC');
            $table->unsignedTinyInteger('upload_status')->nullable();

            $table->foreign(['pda_pda_id'], 'FKD4E244F253504BCC')->references(['pda_id'])->on('pda_profile')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pda_log');
    }
}
