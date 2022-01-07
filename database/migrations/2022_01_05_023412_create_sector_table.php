<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSectorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sector', function (Blueprint $table) {
            $table->unsignedBigInteger('sector_id')->primary();
            $table->string('sector_name', 32);
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FKC9FB57661F6798AB');

            $table->foreign(['company_company_id'], 'FKC9FB57661F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->unique(['sector_name', 'company_company_id'], 'company_sector_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sector');
    }
}
