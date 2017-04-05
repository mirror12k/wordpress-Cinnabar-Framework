<?php



class BasePlugin
{
	// public static $plugin_name = 'base-plugin';

	public function load_plugin()
	{
		$this->load_base_hooks();
		$this->load_hooks();
	}

	public function load_base_hooks()
	{
		register_activation_hook(plugin_basename($this->plugin_dir('/' . $this->plugin_name . '.php')), array($this, 'wordpress_activate'));
		add_action('init', array($this, 'wordpress_init'));
		add_action('wp_loaded', array($this, 'wordpress_loaded'));
	}

	// overridable
	public function load_hooks()
	{}

	// overridable
	public function wordpress_activate()
	{}

	// overridable
	public function wordpress_init()
	{}

	// overridable
	public function wordpress_loaded()
	{}

	// utility functions
	public function plugin_dir($suffix='')
	{
		return WP_PLUGIN_DIR . '/' . $this->plugin_name . $suffix;
	}
	
	public function plugin_url($suffix='')
	{
		return plugins_url() . '/' . $this->$plugin_name . $suffix;
	}

	public function redirect_home($suffix='')
	{
		$this->redirect(site_url() . $suffix);
	}

	public function redirect($location)
	{
		wp_redirect($location);
		exit;
	}
}


