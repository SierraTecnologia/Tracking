<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEstatisticasTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::hasTable('estatisticas') || Schema::create('estatisticas', function (Blueprint $table) {

            // Set the storage engine and primary key
            $table->engine = 'InnoDB';
            $table->increments('id');
            
            $table->string('fonte')->nullable();
            $table->string('nome')->nullable();
            $table->string('periodo')->nullable();
            $table->string('date')->nullable();
            
            $table->string('result')->nullable();


            $table->json('extra_type')->nullable();

            // Automatic columns
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('estatisticas');
    }
}
