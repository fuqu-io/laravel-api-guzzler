<?php

namespace FuquIo\ApiGuzzler;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
 * @package FuquIo\LaravelCors
 */
class ServiceProvider extends BaseServiceProvider{
	CONST VENDOR_PATH = 'fuqu-io/laravel-api-guzzler';
	CONST SHORT_NAME = 'fuqu-guzzler';

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot(){

		$this->bootConfig();

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register(){

	}


	/**
	 * @internal
	 */
	private function bootConfig(){
		$this->publishes([__DIR__ . '/../config/main.php' => config_path(SELF::SHORT_NAME . '.php')], 'config');
		$this->mergeConfigFrom(__DIR__ . '/../config/main.php', SELF::SHORT_NAME);
	}

}