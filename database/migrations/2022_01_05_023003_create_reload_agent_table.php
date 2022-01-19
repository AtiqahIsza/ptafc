<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReloadAgentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reload_agent', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('agent_type')->nullable();
            $table->decimal('current_prepaid_balance')->nullable();
            $table->string('full_name', 128);
            $table->string('ic_number', 12);
            $table->string('password');
            $table->string('phone_number', 16);
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('username', 16)->unique('agent_username_idx');
            $table->unsignedBigInteger('company_id')->nullable()->index('FK31E2D0DF1F6798AB');
            $table->unsignedBigInteger('parent_agent_id')->nullable()->index('FK31E2D0DF6350F434');
            $table->decimal('minimum_balance')->nullable();

            $table->foreign(['parent_agent_id'], 'FK31E2D0DF6350F434')->references(['id'])->on('reload_agent')->onDelete('cascade');
            $table->foreign(['company_id'], 'FK31E2D0DF1F6798AB')->references(['id'])->on('company')->onDelete('cascade');
            $table->unique(['ic_number', 'company_id'], 'agent_ic_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reload_agent');
    }
}
