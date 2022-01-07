<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionSchemeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_scheme', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_id')->primary();
            $table->unsignedTinyInteger('agent_type')->nullable();
            $table->decimal('commission')->nullable();
            $table->unsignedInteger('from_value')->nullable();
            $table->unsignedInteger('to_value')->nullable();
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FK22F067791F6798AB');
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedBigInteger('user_user_id')->nullable()->index('FK22F06779AF484C88');

            $table->foreign(['user_user_id'], 'FK22F06779AF484C88')->references(['id'])->on('users')->onDelete('cascade');
            $table->foreign(['company_company_id'], 'FK22F067791F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');
            $table->unique(['company_company_id', 'from_value', 'to_value', 'agent_type'], 'commission_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_scheme');
    }
}
