<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('accident')->nullable();
            $table->decimal('actual_no_of_kilometeres')->nullable();
            $table->string('actual_start_time')->nullable();
            $table->integer('adult_count')->nullable();
            $table->integer('adult_count_off')->nullable();
            $table->string('arrival_time')->nullable();
            $table->unsignedTinyInteger('break_down')->nullable();
            $table->bigInteger('bus_id')->nullable();
            $table->string('bus_plate_number')->nullable();
            $table->string('bus_registration_number')->nullable();
            $table->string('bus_stop_description')->nullable();
            $table->string('bus_stop_id')->nullable();
            $table->integer('child_count')->nullable();
            $table->integer('child_count_off')->nullable();
            $table->string('operator_name', 128)->nullable();
            $table->string('description', 256)->nullable();
            $table->string('driver_name')->nullable();
            $table->unsignedTinyInteger('early_trip')->nullable();
            $table->unsignedTinyInteger('late_trip')->nullable();
            $table->integer('missed_trip')->nullable();
            $table->integer('monthly_pass')->nullable();
            $table->integer('monthly_pass_count_off')->nullable();
            $table->integer('no_of_trips_planned')->nullable();
            $table->integer('no_of_kilometeres')->nullable();
            $table->string('od')->nullable();
            $table->integer('orphan')->nullable();
            $table->integer('orphan_count_off')->nullable();
            $table->integer('other_count')->nullable();
            $table->integer('other_count_off')->nullable();
            $table->unsignedTinyInteger('panic_button_alert')->nullable();
            $table->bigInteger('reverse_trip')->nullable();
            $table->bigInteger('route_id')->nullable();
            $table->string('route_number')->nullable();
            $table->integer('senior_citizen_count')->nullable();
            $table->integer('senior_citizen_count_off')->nullable();
            $table->dateTime('service_date')->nullable();
            $table->string('service_group', 40)->nullable();
            $table->string('service_start_time')->nullable();
            $table->bigInteger('stage_id')->nullable();
            $table->string('stage_name')->nullable();
            $table->integer('stage_order')->nullable();
            $table->string('start_point')->nullable();
            $table->integer('student_count')->nullable();
            $table->integer('student_count_off')->nullable();
            $table->integer('total_off')->nullable();
            $table->integer('total_on')->nullable();
            $table->integer('transfer_count')->nullable();
            $table->bigInteger('trip_id')->nullable();
            $table->string('bus_age')->nullable();
            $table->string('bus_replace_id')->nullable();
            $table->string('bus_schedule_trip_id')->nullable();
            $table->string('voc')->nullable();
            $table->decimal('adult_fare_sum_off')->nullable();
            $table->decimal('adult_fare_sum_on')->nullable();
            $table->decimal('others_fare_sum_off')->nullable();
            $table->decimal('others_fare_sum_on')->nullable();
            $table->integer('bus_scheduler_id')->nullable();
            $table->integer('sector_sector_id')->nullable();
            $table->boolean('is_process')->nullable()->default(false);
            $table->integer('no_of_bus_stop_travel')->nullable();
            $table->integer('pda_replcement_id')->nullable();
            $table->bigInteger('end_stage_id')->nullable();
            $table->bigInteger('start_stage_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_data');
    }
}
