<?php



namespace Cinnabar;

class BasePluginMixin
{
	public function __construct($app)
	{
		$this->app = $app;
	}

	// overridable api
	public function load_hooks()
	{}

	public function register()
	{}

	public function wordpress_activate()
	{}

	public function wordpress_init()
	{}

	public function wordpress_admin_init()
	{}

	public function wordpress_admin_menu()
	{}

	public function wordpress_loaded()
	{}
}


