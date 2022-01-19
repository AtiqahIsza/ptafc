<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionKeyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_key', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_created')->nullable();
            $table->string('trx_key', 64)->unique('trx_key');
            $table->unsignedBigInteger('agent_id')->nullable()->index('FK4B4D017E1EDF5D0A');

            $table->foreign(['agent_id'], 'FK4B4D017E1EDF5D0A')->references(['id'])->on('reload_agent')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_key');
    }
}
