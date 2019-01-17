<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataFromNinjaReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ninja_reports', function (Blueprint $table) {
            $table->string('data_from', 20)->after('attachment_type');
            $table->string('ends_on')->after('ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ninja_reports', function (Blueprint $table) {
            $table->dropColumn(['data_from','ends_on']);
        });
    }
}
