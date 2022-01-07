<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRouteStageView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW `route_stage` AS select distinct `r`.`route_id` AS `route_id`,`s`.`stage_id` AS `stage_id`,`r`.`route_number` AS `route_number`,`s`.`stage_name` AS `start_point`,`r`.`company_company_id` AS `company_company_id` from (`mybas`.`route` `r` join `mybas`.`stage` `s`) where `s`.`route_route_id` = `r`.`route_id`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS `route_stage`");
    }
}
