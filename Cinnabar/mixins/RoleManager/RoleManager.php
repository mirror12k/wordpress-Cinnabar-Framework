<?php



namespace Cinnabar;

class RoleManager extends BasePluginMixin
{
	public $registered_role_capabilities = array();



	public function register_role_capabilities($role_capabilities)
	{
		foreach ($role_capabilities as $role_name => $capabilities)
		{
			if (isset($this->registered_role_capabilities[$action]))
				throw new \Exception("cinnabar ajax action '$action' is registered twice");

			$this->registered_ajax_actions[$action] = $description;
		}
	}

	public function wordpress_loaded()
	{
		if (is_admin())
		{
			$this->update_role_capabilities();
		}
	}

	public function update_role_capabilities()
	{
		foreach ($this->registered_role_capabilities as $role_name => $capabilities)
		{
			$role = get_role($role_name);
			if ($role === null)
				throw new \Exception("invalid role '$role_name' registered");

			foreach ($capabilities as $cap => $enable)
				if ($enable)
					$role->add_cap($cap);
				else
					$role->remove_cap($cap);
		}
	}
}


