<?php

namespace Cinnabar\Mixin;


// wordpress is doing dumb things again where get_plugin_data isn't defined even in wordpress_loaded
require_once ABSPATH . '/wp-admin/includes/plugin.php';

class UpdateTriggerManager extends \Cinnabar\BasePluginMixin
{

	// // chronological version history used to execute update hooks in sequence
	// // must be defined in the main plugin class with a valid version history
	// public $version_history = array(
	// 	'0.0.1',
	// 	'0.0.2',
	// );

	public $scheduled_version_update = null;



	public function register()
	{
		// plugin settings specifically for the updater
		// we use the first listed version history as the default
		$this->app->register_plugin_options(array(
			'cinnabar-update-trigger-manager-settings' => array(
				'title' => 'Cinnabar Update Trigger Manager Section',
				'fields' => array(
					'cinnabar-update-trigger-manager-active-plugin-version' => array(
						'label' => 'currently active plugin version',
						'default' => $this->app->version_history[0],
						// 'option_type' => 'disabled',
					),
				),
			),
		));
	}

	public function wordpress_loaded()
	{
		if (is_admin())
		{
			$this->update_update_triggers();
		}
	}

	public function update_update_triggers()
	{
		// determine the current active version
		$plugin_data = get_plugin_data($this->app->plugin_dir('/' . $this->app->plugin_name . '.php'), false, false);
		$active_version = $plugin_data['Version'];

		$previous_version = $this->app->get_plugin_option('cinnabar-update-trigger-manager-active-plugin-version');
		// error_log("debug update_update_triggers: $active_version vs $previous_version");

		// if it is not the same as the one in the database, we need schedule an update trigger
		if ($previous_version !== $active_version)
		{
			$plugin_name = $this->app->plugin_name;
			error_log("[$plugin_name] updating version from $previous_version to $active_version ...");
			$this->trigger_update_hooks($previous_version, $active_version);
			$this->app->set_plugin_option('cinnabar-update-trigger-manager-active-plugin-version', $active_version);
		}
	}

	public function trigger_update_hooks($version_start, $version_end)
	{
		$i = 0;
		// find the current version number we are on
		while ($i < count($this->app->version_history) && $this->app->version_history[$i] !== $version_start)
			$i++;
		$i++;
		// step through the versions list
		while ($i < count($this->app->version_history))
		{
			$version = $this->app->version_history[$i];
			// if there are any update hooks to trigger, call them
			$this->do_plugin_version($version);

			// stop processing hooks if we have reached our end version
			if ($this->app->version_history[$i] === $version_end)
				break;

			$i++;
		}
	}

	public function on_plugin_version($version, $callback, $priority=10)
	{
		$this->app->on_plugin_action("cinnabar_updater__on_version__$version", $callback, 0, $priority);
	}

	public function do_plugin_version($version)
	{
		$this->app->do_plugin_action("cinnabar_updater__on_version__$version", array());
	}
}

