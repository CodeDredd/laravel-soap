<?php

namespace CodeDredd\Soap;

use Illuminate\Support\ServiceProvider;

class SoapServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot()
  {
    // Nothing here
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
  	$this->registerService();
  }

	/**
	 * Register Horizon's services in the container.
	 *
	 * @return void
	 */
	protected function registerService()
	{
		$this->app->bind('Soap', function () {
			return new SoapFactory();
		});
	}
}
