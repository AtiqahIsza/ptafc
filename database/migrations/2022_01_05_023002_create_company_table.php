<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company', function (Blueprint $table) {
            $table->id();
            $table->string('address1', 128);
            $table->string('address2', 128)->nullable();
            $table->string('city', 64)->nullable();
            $table->string('company_name', 128)->unique('company_name_idx');
            $table->string('postcode', 16)->nullable();
            $table->string('state', 64)->nullable();
            $table->unsignedBigInteger('region_id')->nullable()->index('FK38A73C7DF26F9C4D');
            $table->unsignedTinyInteger('close_to_minimum')->nullable();
            $table->decimal('minimum_balance')->nullable();
            $table->unsignedTinyInteger('company_type')->nullable();

            $table->foreign(['region_id'], 'FK38A73C7DF26F9C4D')->references(['id'])->on('region_code')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company');
    }
}
