<?php


class BasePluginMixin
{
	public $role_capabilities = array();
	public $plugin_settings = array();
	public $mixin_widgets = array();


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


