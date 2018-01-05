<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('report_id')->unsigned();
            $table->string('clicks')->nullable();
            $table->string('impressions')->nullable();
            $table->string('ctr')->nullable();
            $table->string('cpc')->nullable();
            $table->string('cpm')->nullable();
            $table->string('spend')->nullable();
            $table->string('date_start')->nullable();
            $table->string('date_stop')->nullable();
            $table->string('age')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics');
    }
}
