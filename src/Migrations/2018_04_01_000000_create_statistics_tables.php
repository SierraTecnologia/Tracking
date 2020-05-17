<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatisticsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.data'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('session_id');
                $table->nullableMorphs('user');
                $table->integer('status_code');
                $table->text('uri');
                $table->string('method');
                $table->{$this->jsonable()}('server');
                $table->{$this->jsonable()}('input')->nullable();
                $table->timestamp('created_at')->nullable();
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.routes'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('name');
                $table->string('path');
                $table->string('action');
                $table->string('middleware')->nullable();
                $table->{$this->jsonable()}('parameters')->nullable();
                $table->integer('count')->unsigned()->default(0);

                // Indexes
                $table->unique('name');
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.paths'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('host');
                $table->string('locale');
                $table->string('path');
                $table->string('method');
                $table->{$this->jsonable()}('parameters')->nullable();
                $table->integer('count')->unsigned()->default(0);

                // Indexes
                $table->unique(['host', 'path', 'method', 'locale']);
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.agents'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('name');
                $table->string('kind');
                $table->string('family');
                $table->string('version')->nullable();
                $table->integer('count')->unsigned()->default(0);
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.devices'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('family');
                $table->string('model')->nullable();
                $table->string('brand')->nullable();
                $table->integer('count')->unsigned()->default(0);
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.platforms'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('family');
                $table->string('version')->nullable();
                $table->integer('count')->unsigned()->default(0);
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.geoips'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->string('client_ip');
                $table->string('latitude');
                $table->string('longitude');
                $table->char('country_code', 2);
                $table->{$this->jsonable()}('client_ips')->nullable();
                $table->boolean('is_from_trusted_proxy')->default(0);
                $table->string('division_code')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('timezone')->nullable();
                $table->string('city')->nullable();
                $table->integer('count')->unsigned()->default(0);

                // Indexes
                $table->unique(['client_ip', 'latitude', 'longitude']);
            }
        );
        Schema::create(
            \Illuminate\Support\Facades\Config::get('tracking.statistics.tables.requests'), function (Blueprint $table) {
                // Columns
                $table->increments('id');
                $table->integer('route_id')->unsigned();
                $table->integer('agent_id')->unsigned();
                $table->integer('device_id')->unsigned();
                $table->integer('platform_id')->unsigned();
                $table->integer('path_id')->unsigned();
                $table->integer('geoip_id')->unsigned();
                $table->nullableMorphs('user');
                $table->string('session_id');
                $table->integer('status_code');
                $table->string('protocol_version')->nullable();
                $table->text('referer')->nullable();
                $table->string('language');
                $table->boolean('is_no_cache')->default(0);
                $table->boolean('wants_json')->default(0);
                $table->boolean('is_secure')->default(0);
                $table->boolean('is_json')->default(0);
                $table->boolean('is_ajax')->default(0);
                $table->boolean('is_pjax')->default(0);
                $table->timestamp('created_at')->nullable();

                // Indexes
                $table->foreign('route_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.routes'))
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('agent_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.agents'))
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('device_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.devices'))
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('platform_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.platforms'))
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('path_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.paths'))
                    ->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('geoip_id')->references('id')->on(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.geoips'))
                    ->onDelete('cascade')->onUpdate('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.requests'));





        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.geoips'));
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.platforms'));
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.devices'));


        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.agents'));
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.paths'));
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.routes'));
        Schema::dropIfExists(\Illuminate\Support\Facades\Config::get('tracking.statistics.tables.data'));
    }

    /**
     * Get jsonable column data type.
     *
     * @return string
     */
    protected function jsonable(): string
    {
        $driverName = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dbVersion = DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        $isOldVersion = version_compare($dbVersion, '5.7.8', 'lt');

        return $driverName === 'mysql' && $isOldVersion ? 'text' : 'json';
    }
}
