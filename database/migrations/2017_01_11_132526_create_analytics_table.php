<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::hasTable('analytics') || Schema::create('analytics', function (Blueprint $table) {
                $table->increments('id');
                $table->string('token')->nullable();
                $table->string('business_code')->nullable();
                $table->text('data')->nullable();
                $table->nullableTimestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('analytics');
    }
}
