<?php



namespace Cinnabar\Mixin;

class SyntheticFirewallManager extends \Cinnabar\BasePluginMixin
{
	public $registered_synthetic_firewall_groups = array();
	public $registered_synthetic_firewall_pages = array();

	public function load_hooks() {
		$this->app->on_plugin_action('active_synthetic_page_selected', array($this, 'active_synthetic_page_selected'));
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

	public function active_synthetic_page_selected($key) {
		if (isset($this->registered_synthetic_firewall_pages[$key])) {
			$firewall_page = $this->registered_synthetic_firewall_pages[$key];
			$group = $this->registered_synthetic_firewall_groups[$firewall_page];

			if (isset($group['require_logged_in']) && $group['require_logged_in'])
				if (!is_user_logged_in())
					$this->app->redirect(isset($group['else_redirect']) ? $group['else_redirect'] : '/');

			if (isset($group['require_logged_out']) && $group['require_logged_out'])
				if (is_user_logged_in())
					$this->app->redirect(isset($group['else_redirect']) ? $group['else_redirect'] : '/');
		}
	}
}


