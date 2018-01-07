<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveFieldToAccountsAndAdAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->after('token');
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(1)->after('ad_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_active']);
        });

        Schema::table('ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_active']);
        });
    }
}
