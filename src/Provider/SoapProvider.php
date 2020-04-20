<?php

namespace AndyJessop\Starter\Providers;

use Illuminate\Support\ServiceProvider;
use App;
use Config;
use Lang;
use View;

class StarterServiceProvider extends ServiceProvider{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider and dependencies.
	 * @return void
	 */
	public function register()
	{
		$this->registerDependencies();
		$this->registerStarter();
	}

	/**
	 * Set up aliases, setup routes, publish assets.
	 * @return void
	 */
	public function boot()
	{
		$this->aliasDependencies();
		$this->setupRoutes($this->app->router);
		$this->publishAssets();
	}

	/**
	 * Register dependencies' service providers.
	 * @return void
	 */
	private function registerDependencies()
	{
		App::register('Collective\Html\HtmlServiceProvider');
	}

	/**
	 * Register package service provider.
	 * @return void
	 */
	private function registerStarter()
	{
		$this->app->bind('soap',function($app){
			return new Starter($app);
		});
	}

	/**
	 * Create aliases for dependencies.
	 * @return void
	 */
	private function aliasDependencies()
	{
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Html', 'Collective\Html\HtmlFacade');
		$loader->alias('Form', 'Collective\Html\FormFacade');
	}
	/**
	 * Define the routes for the package.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function setupRoutes(Router $router)
	{
		$router->group(['namespace' => 'AndyJessop\Starter\Http\Controllers'], function($router)
		{
			require __DIR__.'/../Http/routes.php';
		});
	}

	/**
	 * Publish package assets.
	 * @return void
	 */
	private function publishAssets()
	{
		$this->publishes([
			realpath(__DIR__.'/../../resources/Views') => base_path('resources/views/vendor/andyjessop/starter'),
		], 'views');
		$this->publishes([
			realpath(__DIR__.'/../../resources/Assets') => public_path('assets'),
		], 'public');
		$this->publishes([
			realpath(__DIR__.'/../../resources/Config') => config_path('')
		], 'config');
	}
}