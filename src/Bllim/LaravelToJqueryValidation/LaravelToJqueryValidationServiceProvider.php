<?php namespace Bllim\LaravelToJqueryValidation;

use Illuminate\Support\ServiceProvider;

class LaravelToJqueryValidationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('bllim/laravel-to-jquery-validation');
		$this->app->register('Illuminate\Html\HtmlServiceProvider');
		
		$this->app->bindShared('form', function($app)
		{
			$converter = \Config::get('laravel-to-jquery-validation::converter');
			$form = new $converter($app->make('html'), $app->make('url'), $app->make('session.store')->getToken());
			return $form->setSessionStore($app->make('session.store'));
		});


	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('form');
	}

}
