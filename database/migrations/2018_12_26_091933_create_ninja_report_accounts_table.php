<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNinjaReportAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ninja_report_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("report_id");
            $table->integer("account_id")->unsigned();
            $table->integer('ad_account_id')->unsigned();
            $table->integer('property_id')->default(0);
            $table->integer('profile_id')->default(0);

            $table->foreign('ad_account_id')->references('id')->on('ad_accounts')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ninja_report_accounts');
    }
}
