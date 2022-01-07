<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyMonthlySalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_monthly_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_sales_id')->primary();
            $table->decimal('amount', 11)->nullable();
            $table->unsignedTinyInteger('fare_type')->nullable();
            $table->unsignedTinyInteger('sales_month')->nullable();
            $table->unsignedSmallInteger('sales_year')->nullable();
            $table->unsignedBigInteger('ticket_count')->nullable();
            $table->unsignedBigInteger('company_company_id')->nullable()->index('FKEE582E181F6798AB');

            $table->foreign(['company_company_id'], 'FKEE582E181F6798AB')->references(['company_id'])->on('company')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_monthly_sales');
    }
}
