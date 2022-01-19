<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_staff', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 128)->nullable();
            $table->string('password');
            $table->unsignedTinyInteger('status')->nullable();
            $table->string('username', 16)->unique('staff_username_idx');
            $table->unsignedBigInteger('agent_id')->nullable()->index('FK76EF51261EDF5D0A');

            $table->foreign(['agent_id'], 'FK76EF51261EDF5D0A')->references(['id'])->on('reload_agent')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_staff');
    }
}
