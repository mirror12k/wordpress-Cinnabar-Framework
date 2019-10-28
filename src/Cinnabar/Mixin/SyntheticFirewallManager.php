<?php



namespace Cinnabar\Mixin;

class SyntheticFirewallManager extends \Cinnabar\BasePluginMixin
{
	public $registered_synthetic_firewall_groups = array();
	public $registered_synthetic_firewall_pages = array();

	public function load_hooks() {
		$this->app->on_plugin_action('active_synthetic_page_selected', array($this, 'active_synthetic_page_selected'));
		$this->app->on_plugin_action('check_permissions_ajax_cinnabar_action', array($this, 'check_permissions_ajax_cinnabar_action'));
	}

	public function register_firewall_groups($args) {
		foreach ($args as $key => $group)
			if (isset($this->registered_synthetic_firewall_groups[$key]))
				throw new \Exception("synthetic firewall group '$key' is registered twice");
			else
				$this->registered_synthetic_firewall_groups[$key] = $group;
	}

	public function register_firewall_pages($args) {
		foreach ($args as $key => $page)
			if (isset($this->registered_synthetic_firewall_pages[$key]))
				throw new \Exception("synthetic firewall page '$key' is registered twice");
			else
				$this->registered_synthetic_firewall_pages[$key] = $page;
	}

	public function get_firewall_page_for_key($key) {
		if (isset($this->registered_synthetic_firewall_pages[$key]))
			return $this->registered_synthetic_firewall_pages[$key];
		else {
			foreach ($this->registered_synthetic_firewall_pages as $index => $value) {
				// if our index ends with a star
				if (substr($index, -1) === "*") {
					// check if the part before the star corresponds with the key
					$wild_index = substr($index, 0, -1);
					if ($wild_index === substr($key, 0, strlen($wild_index)))
						return $value;
				}
			}
		}

		return null;
	}

	public function active_synthetic_page_selected($key) {
		$firewall_page = $this->get_firewall_page_for_key($key);
		if ($firewall_page !== null) {
			$group = $this->registered_synthetic_firewall_groups[$firewall_page];

			if (isset($group['require_logged_in']) && $group['require_logged_in'])
				if (!is_user_logged_in()) {
					error_log("user must be logged in for $key");
					$this->app->redirect(isset($group['else_redirect']) ? $group['else_redirect'] : '/');
				}

			if (isset($group['require_logged_out']) && $group['require_logged_out'])
				if (is_user_logged_in()) {
					error_log("user must be logged out for $key");
					$this->app->redirect(isset($group['else_redirect']) ? $group['else_redirect'] : '/');
				}

			if (isset($group['require_permissions']))
				foreach ($group['require_permissions'] as $permission)
					if (!user_can(get_current_user_id(), $permission)) {
						error_log("user must have permission $permission for $key");
						$this->app->redirect(isset($group['else_redirect']) ? $group['else_redirect'] : '/');
					}
		}
	}

	public function check_permissions_ajax_cinnabar_action($key) {
		$firewall_page = $this->get_firewall_page_for_key("ajax:$key");
		if ($firewall_page !== null) {
			$group = $this->registered_synthetic_firewall_groups[$firewall_page];

			if (isset($group['require_logged_in']) && $group['require_logged_in'])
				if (!is_user_logged_in()) {
					error_log("user must be logged in for $key");
					throw new \Exception("permission denied");
				}

			if (isset($group['require_logged_out']) && $group['require_logged_out'])
				if (is_user_logged_in()) {
					error_log("user must be logged out for $key");
					throw new \Exception("permission denied");
				}

			if (isset($group['require_permissions']))
				foreach ($group['require_permissions'] as $permission)
					if (!user_can(get_current_user_id(), $permission)) {
						error_log("user must have permission $permission for $key");
						throw new \Exception("permission denied: user lacks permission '$permission'");
					}
		}
	}
}


