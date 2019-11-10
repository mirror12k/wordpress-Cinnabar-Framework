<?php



namespace Cinnabar\Mixin;

class RoleManager extends \Cinnabar\BasePluginMixin
{
	public $registered_roles = array();
	public $registered_role_capabilities = array();

	public function register_role($role_capabilities) {
		foreach ($role_capabilities as $role_name => $role_data) {
			if (isset($this->registered_roles[$role_name]))
				throw new \Exception("role '$role_name' is registered twice");

			$this->registered_roles[$role_name] = $role_data;
		}
	}

	public function register_role_capabilities($role_capabilities) {
		foreach ($role_capabilities as $role_name => $capabilities) {
			if (isset($this->registered_role_capabilities[$role_name]))
				throw new \Exception("role capability '$role_name' is registered twice");

			$this->registered_role_capabilities[$role_name] = $capabilities;
		}
	}

	public function wordpress_activate() {
		$this->update_roles();
		$this->update_role_capabilities();
	}

	public function update_role_capabilities() {
		foreach ($this->registered_role_capabilities as $role_name => $capabilities) {
			$role = get_role($role_name);
			if ($role === null)
				throw new \Exception("invalid role '$role_name' for registered capability");

			foreach ($capabilities as $cap => $enable)
				if ($enable)
					$role->add_cap($cap);
				else
					$role->remove_cap($cap);
		}
	}

	public function update_roles() {
		foreach ($this->registered_roles as $role_name => $role_data)
			add_role($role_name, $role_data['display_name'], $role_data['capabilities']);
	}
}


