<?php



class BasePlugin
{
	// public $plugin_name = 'base-plugin';
	public $plugin_options = array();
	public $default_plugin_options = array();

	public function load_plugin()
	{
		$this->load_plugin_options();
		$this->load_base_hooks();
		$this->load_hooks();
	}

	public function load_plugin_options()
	{
		foreach ($this->plugin_options as $section => $section_data)
			foreach ($section_data['fields'] as $setting => $args)
				$this->default_plugin_options[$setting] = isset($args['default']) ? $args['default'] : null;
	}

	public function load_base_hooks()
	{
		register_activation_hook(plugin_basename($this->plugin_dir('/' . $this->plugin_name . '.php')), array($this, 'wordpress_activate'));
		add_action('init', array($this, 'wordpress_init'));
		add_action('admin_init', array($this, 'wordpress_admin_init'));
		add_action('admin_menu', array($this, 'wordpress_admin_menu'));
		add_action('wp_loaded', array($this, 'wordpress_loaded'));

		add_filter("plugin_action_links_" . plugin_basename($this->plugin_dir() . '/' . $this->plugin_name . '.php'), array($this, 'baseplugin_action_links'));
		add_action('admin_init', array($this, 'baseplugin_add_options'));
		add_action('admin_menu', array($this, 'baseplugin_add_options_page'));
	}

	public function baseplugin_add_options()
	{
		foreach ($this->plugin_options as $section => $section_data)
		{
		 	add_settings_section(
				$this->plugin_name . '__options_section__' . $section,
				$section_data['title'],
				'intval',
				$this->plugin_name . '__options_group'
			);
			
			// register all plugin settings
			foreach ($section_data['fields'] as $setting => $args)
			{
				$sanitize_callback = isset($args['sanitize_callback']) ? $args['sanitize_callback'] : null;
				register_setting($this->plugin_name . '__options_group', $this->plugin_name . '__option__' . $setting, $sanitize_callback);

				add_settings_field(
					$this->plugin_name . '__option__' . $setting,
					$args['label'],
					array($this, 'baseplugin_options_field'),
					$this->plugin_name . '__options_group',
					$this->plugin_name . '__options_section__' . $section,
					array(
						'setting_name' => $this->plugin_name . '__option__' . $setting,
						'setting_args' => $args,
					)
				);
			}
		}
	}

	public function baseplugin_add_options_page()
	{
		if (!empty($this->plugin_options))
			add_options_page('Plugin Settings', $this->plugin_name, 'manage_options', $this->plugin_name . '-settings', array($this, 'baseplugin_settings_page'));
	}

	public function baseplugin_settings_page()
	{
?>

<div class="wrap">
	<form method="post" action="options.php">
		<?php settings_fields($this->plugin_name . '__options_group'); ?>
		<?php do_settings_sections($this->plugin_name . '__options_group'); ?>

		<?php submit_button(); ?>
	</form>
</div>

<?php
	}


	public function baseplugin_options_field($args)
	{
		$setting = $args['setting_name'];
		echo "<input type='text' name='$setting' id='$setting' value='" . htmlentities(get_option($setting)) . "' />";
	}

	public function baseplugin_action_links($links)
	{
		if (!empty($this->plugin_options))
			$links[] = '<a href="options-general.php?page=' . $this->plugin_name . '-settings' . '">Settings</a>';
		return $links;
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
	public function wordpress_admin_init()
	{}

	// overridable
	public function wordpress_admin_menu()
	{}

	// overridable
	public function wordpress_loaded()
	{}

	// mixin api
	public function register_plugin_options($new_options)
	{
		foreach ($new_options as $section => $section_data)
			if (isset($this->plugin_options[$section]))
				throw new Exception("redefinition of plugin options section '$section'");
			else
				$this->plugin_options[$section] = $section_data;
	}

	// utility functions
	public function get_plugin_option($setting)
	{
		return get_option($this->plugin_name . '__option__' . $setting, $this->default_plugin_options[$setting]);
	}

	public function set_plugin_option($setting, $value)
	{
		return update_option($this->plugin_name . '__option__' . $setting, $value);
	}

	public function plugin_dir($suffix='')
	{
		return WP_PLUGIN_DIR . '/' . $this->plugin_name . $suffix;
	}
	
	public function plugin_url($suffix='')
	{
		return plugins_url() . '/' . $this->plugin_name . $suffix;
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

	public function do_plugin_action($action, $args)
	{
		$prefix = $this->plugin_name . "__action__";
		$actions = array_merge(array("$prefix$action"), $args);
		// echo ("calling do_action with " . json_encode($actions));
		call_user_func_array('do_action', $actions);
	}

	public function on_plugin_action($action, $callback, $arg_count=1, $priority=10)
	{
		$prefix = $this->plugin_name . "__action__";
		add_action("$prefix$action", $callback, $priority, $arg_count);
	}
}


