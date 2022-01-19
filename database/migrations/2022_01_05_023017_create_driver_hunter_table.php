<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverHunterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_hunter', function (Blueprint $table) {
            $table->id();
            $table->string('address1', 128);
            $table->string('address2', 128)->nullable();
            $table->string('city', 64)->nullable();
            $table->decimal('commission')->nullable();
            $table->string('hunter_name', 128);
            $table->string('postcode', 16)->nullable();
            $table->string('state', 64)->nullable();
            $table->unsignedBigInteger('region_id')->nullable()->index('FK5EC3E777F26F9C4D');
            $table->string('hunter_code', 32);
            $table->dateTime('date_created')->nullable();

            $table->foreign(['region_id'], 'FK5EC3E777F26F9C4D')->references(['id'])->on('region_code')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_hunter');
    }
}
