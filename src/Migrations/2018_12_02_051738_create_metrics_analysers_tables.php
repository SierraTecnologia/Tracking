<?php


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetricsAnalysersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // @todo Falta fazer aqui 
        
        Schema::create(
            'metric_result_types', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->string('name', 255)->nullable();
                $table->string('type', 255)->nullable(); //(int, decimal)
                // $table->string('tatuageable_id');
                // $table->string('tatuageable_type', 255);
                $table->timestamps();
                $table->softDeletes();
            }
        );



        /**
         * 
         */
        
        Schema::create(
            'metrics', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->longText('text')->nullable();
                // $table->string('tatuageable_id');
                // $table->string('tatuageable_type', 255);
                $table->timestamps();
                $table->softDeletes();
            }
        );

        Schema::create(
            'metric_analyses', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->longText('text')->nullable();
                // $table->string('tatuageable_id');
                // $table->string('tatuageable_type', 255);
                $table->timestamps();
                $table->softDeletes();
            }
        );
        
        Schema::create(
            'metric_analyse_results', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->longText('text')->nullable();
                // $table->string('tatuageable_id');
                // $table->string('tatuageable_type', 255);
                $table->timestamps();
                $table->softDeletes();
            }
        );
        // ^ array:2 [
        //     "Targets" => array:2 [
        //       "File" => 272
        //       "Directory" => 100
        //     ]
        //     "Extensions" => array:3 [
        //       "Json" => 5
        //       "Php" => 245
        //       "Md" => 2
        //     ]
        //   ]
          

        Schema::create(
            'analyser_results', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('md5_name');
                $table->string('result');
                $table->string('md5_result');
                $table->integer('status');
                $table->string('reference_id')->nullable();
                $table->string('bot_runner_id');
                $table->timestamps();
            }
        );
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analyser_results');
    }
}
