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
		add_action('init', array($this, 'wordpress_init'));
	}

	// overridable
	public function wordpress_init()
	{}

	// overridable
	public function load_hooks()
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


