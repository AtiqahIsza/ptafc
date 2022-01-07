<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateBusSchedulerView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW `bus_scheduler` AS select `mybas`.`bus_scheduler_history`.`schedule_id` AS `schedule_id`,`mybas`.`bus_scheduler_history`.`no_of_kilometers` AS `no_of_kilometers`,`mybas`.`bus_scheduler_history`.`no_of_trips` AS `no_of_trips`,`mybas`.`bus_scheduler_history`.`schedule_date` AS `schedule_date`,`mybas`.`bus_scheduler_history`.`status` AS `status`,`mybas`.`bus_scheduler_history`.`time` AS `time`,`mybas`.`bus_scheduler_history`.`bus1_bus_id` AS `bus1_bus_id`,`mybas`.`bus_scheduler_history`.`bus2_bus_id` AS `bus2_bus_id`,`mybas`.`bus_scheduler_history`.`route_route_id` AS `route_route_id`,`mybas`.`bus_scheduler_history`.`trip_id` AS `trip_id`,`mybas`.`bus_scheduler_history`.`inbound_distance` AS `inbound_distance`,`mybas`.`bus_scheduler_history`.`outbound_distance` AS `outbound_distance`,0 AS `trip_type` from `mybas`.`bus_scheduler_history` union select `d`.`scheduler_detail_id` AS `scheduler_detail_id`,`m`.`no_of_kilometers` AS `no_of_kilometers`,`m`.`no_of_trips` AS `no_of_trips`,`m`.`schedule_date` AS `schedule_date`,`m`.`status` AS `status`,`d`.`time` AS `time`,`d`.`bus1_bus_id` AS `bus1_bus_id`,`d`.`bus2_bus_id` AS `bus2_bus_id`,`m`.`route_route_id` AS `route_route_id`,`d`.`trip_id` AS `trip_id`,`m`.`inbound_distance` AS `inbound_distance`,`m`.`outbound_distance` AS `outbound_distance`,`m`.`trip_type` AS `trip_type` from (`mybas`.`bus_scheduler_mstr` `m` join `mybas`.`bus_scheduler_details` `d`) where `d`.`scheduler_mstr_id` = `m`.`schedule_id`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS `bus_scheduler`");
    }
}
