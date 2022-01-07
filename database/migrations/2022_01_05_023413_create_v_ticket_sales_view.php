<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateVTicketSalesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW `v_ticket_sales` AS select `mybas`.`ticket_sales_transaction`.`sales_id` AS `sales_id`,dayofmonth(`mybas`.`ticket_sales_transaction`.`sales_date`) AS `sales_day`,month(`mybas`.`ticket_sales_transaction`.`sales_date`) AS `sales_month`,year(`mybas`.`ticket_sales_transaction`.`sales_date`) AS `sales_year`,`mybas`.`ticket_sales_transaction`.`fare_type` AS `fare_type`,`mybas`.`ticket_sales_transaction`.`amount` AS `amount`,`mybas`.`ticket_sales_transaction`.`busDriver_driver_id` AS `busDriver_driver_id`,`mybas`.`ticket_sales_transaction`.`pda_pda_id` AS `pda_pda_id` from `mybas`.`ticket_sales_transaction` order by `mybas`.`ticket_sales_transaction`.`sales_date` desc");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS `v_ticket_sales`");
    }
}
