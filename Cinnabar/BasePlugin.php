<?php



namespace Cinnabar;

require_once 'BasePluginMixin.php';

class BasePlugin
{
	// required member value which matches the directory name of the plugin
	// public $plugin_name = 'base-plugin';

	// overloadable list of string classes representing the mixins
	public $mixins = array();


	public $plugin_options = array();
	public $default_plugin_options = array();

	public $global_scripts = array();
	public $global_style_sheets = array();


	// entry point, must be called at the start of the plugin to load the application functionality
	public function load_plugin()
	{
		// load the mixins
		$this->load_mixins();
		// load all action/filter hooks
		$this->load_base_hooks();
		$this->load_hooks();
		$this->mixins_load_hooks();
	}


	// mixin api
	public function register_plugin_options($new_options)
	{
		foreach ($new_options as $section => $section_data)
			if (isset($this->plugin_options[$section]))
				throw new \Exception("redefinition of plugin options section '$section'");
			else
				$this->plugin_options[$section] = $section_data;
	}

	public function register_global_scripts($scripts)
	{
		foreach ($scripts as $name => $script)
			$this->global_scripts[$name] = $script;
	}

	public function register_global_style_sheets($style_sheets)
	{
		foreach ($style_sheets as $name => $style_sheet)
			$this->global_style_sheets[$name] = $style_sheet;
	}


	// baseplugin functionality	
	public function load_mixins()
	{
		$mixin_classes = $this->mixins;
		$this->mixins = array();
		foreach ($mixin_classes as $mixin_name => $mixin_class)
		{
			$this->mixins[$mixin_name] = new $mixin_class($this);
			$this->$mixin_name = $this->mixins[$mixin_name];
		}
	}

	public function load_base_hooks()
	{
		// allow all cpt/options/pages registration to happen first
		add_action('init', array($this, 'register'));
		add_action('init', array($this, 'mixins_register'));
		// options are loaded after the plugin and mixins have had a chance to register their options
		add_action('init', array($this, 'load_plugin_options'));

		// standard utility hooks
		register_activation_hook(plugin_basename($this->plugin_dir('/' . $this->plugin_name . '.php')), array($this, 'wordpress_activate'));
		add_action('init', array($this, 'wordpress_init'));
		add_action('admin_init', array($this, 'wordpress_admin_init'));
		add_action('admin_menu', array($this, 'wordpress_admin_menu'));
		add_action('wp_loaded', array($this, 'wordpress_loaded'));

		// standard utility hooks for mixins
		register_activation_hook(plugin_basename($this->plugin_dir('/' . $this->plugin_name . '.php')), array($this, 'mixins_wordpress_activate'));
		add_action('init', array($this, 'mixins_wordpress_init'));
		add_action('admin_init', array($this, 'mixins_wordpress_admin_init'));
		add_action('admin_menu', array($this, 'mixins_wordpress_admin_menu'));
		add_action('wp_loaded', array($this, 'mixins_wordpress_loaded'));

		// baseplugin hooks for registering the options menu
		add_filter("plugin_action_links_" . plugin_basename($this->plugin_dir() . '/' . $this->plugin_name . '.php'), array($this, 'baseplugin_action_links'));
		add_action('admin_init', array($this, 'baseplugin_add_options'));
		add_action('admin_menu', array($this, 'baseplugin_add_options_page'));

		// enqueue global scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'wordpress_enqueue_scripts'));
	}

	public function wordpress_enqueue_scripts()
	{
		foreach ($this->global_scripts as $name => $script)
		{
			if ($script !== null)
				wp_enqueue_script($name, $this->plugin_url('/' . $script));
			else
				wp_enqueue_script($name);
		}
		foreach ($this->global_style_sheets as $name => $style_sheet)
		{
			wp_enqueue_script($name, $this->plugin_url('/' . $style_sheet));
		}
	}

	public function load_plugin_options()
	{
		foreach ($this->plugin_options as $section => $section_data)
			foreach ($section_data['fields'] as $setting => $args)
				$this->default_plugin_options[$setting] = isset($args['default']) ? $args['default'] : null;
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
		if (isset($args['setting_args']['option_type']) && $args['setting_args']['option_type'] === 'boolean')
			echo "<input type='checkbox' name='$setting' id='$setting' value='1' " . (1 == get_option($setting) ? "checked='checked'" : "") . " />";
		else
			echo "<input type='text' name='$setting' id='$setting' value='" . htmlentities(get_option($setting)) . "' />";

	}

	public function baseplugin_action_links($links)
	{
		if (!empty($this->plugin_options))
			$links[] = '<a href="options-general.php?page=' . $this->plugin_name . '-settings' . '">Settings</a>';
		return $links;
	}

	public function mixins_load_hooks()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->load_hooks();
	}

	public function mixins_register()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->register();
	}

	public function mixins_wordpress_activate()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->wordpress_activate();
	}

	public function mixins_wordpress_init()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->wordpress_init();
	}

	public function mixins_wordpress_admin_init()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->wordpress_admin_init();
	}

	public function mixins_wordpress_admin_menu()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->wordpress_admin_menu();
	}

	public function mixins_wordpress_loaded()
	{
		foreach ($this->mixins as $class => $mixin)
			$mixin->wordpress_loaded();
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


