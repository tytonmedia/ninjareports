<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNinjaReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ninja_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('template_id')->nullable()->default(0);
            $table->string('title')->nullable();
            $table->string('frequency')->nullable();
            $table->string('ends_at')->nullable();
            $table->string('email_subject')->nullable();
            $table->longText('recipients')->nullable();
            $table->string('attachment_type')->default('none');
            
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_paused')->default(0);
            $table->timestamp('next_send_time')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ninja_reports');
    }
}
