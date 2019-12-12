<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GravatarServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		$this->app->bind('GravatarService', 'App\Services\GravatarService');

	}

}