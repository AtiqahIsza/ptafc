<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdaProfileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pda_profile', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_created');
            $table->dateTime('date_registered')->nullable();
            $table->string('imei', 40)->nullable()->unique('imei_idx');
            $table->string('pda_key');
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('pda_tag', 16)->nullable();
            $table->unsignedBigInteger('region_id')->nullable()->index('FK38CAA457F26F9C4D');
            $table->unsignedBigInteger('company_id')->nullable()->index('FK38CAA4571F6798AB');
            $table->unsignedTinyInteger('device_type')->nullable();

            $table->foreign(['region_id'], 'FK38CAA457F26F9C4D')->references(['id'])->on('region_code')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK38CAA4571F6798AB')->references(['id'])->on('company')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pda_profile');
    }
}
