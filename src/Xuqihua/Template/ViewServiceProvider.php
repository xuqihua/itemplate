<?php namespace Xuqihua\Template;

use Illuminate\Support\ServiceProvider;
use Xuqihua\Template\TemplateCompiler;

class ViewServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		//$this->package('xuqihua/template');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$app = $this->app;

		$app->bindShared('html.compiler', function($app)
		{
			$cache = $app['path.storage'].'/views';
			return new TemplateCompiler($app['files'], $cache);
		});

		$this->app['view']->addExtension('html', 'template', function() use ($app){
			return new TemplateEngine($app['html.compiler'], $app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}