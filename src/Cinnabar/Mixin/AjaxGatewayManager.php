<?php



namespace Cinnabar\Mixin;

class AjaxGatewayManager extends \Cinnabar\BasePluginMixin
{
	public $registered_ajax_actions = array();
	public $registered_ajax_validators = array();

	public function load_hooks()
	{
		add_action('wp_ajax_cinnabar_ajax_action', array($this, 'cinnabar_action_gateway'));
		add_action('wp_ajax_nopriv_cinnabar_ajax_action', array($this, 'cinnabar_action_gateway'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	public function register()
	{
		$this->register_ajax_validators(array(
			'is_logged_in_validator' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'is_logged_in_validator'),
			'not_null_validator' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'not_null_validator'),
			'cast_bool' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_bool'),
			'cast_int' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_int'),
			'cast_string' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_string'),
			'string_regex_validator' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'string_regex_validator'),
			'cast_bool_array' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_bool_array'),
			'cast_int_array' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_int_array'),
			'cast_string_array' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_string_array'),
			'cast_array' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'cast_array'),
			'parse_json' => array('Cinnabar\\Mixin\\AjaxGatewayManager', 'parse_json'),
		));
	}

	public function register_ajax_actions($actions)
	{
		foreach ($actions as $action => $description)
		{
			if (isset($this->registered_ajax_actions[$action]))
				throw new \Exception("cinnabar ajax action '$action' is registered twice");

			$this->registered_ajax_actions[$action] = $description;
		}
	}

	public function register_ajax_validators($validators)
	{
		foreach ($validators as $name => $callback)
		{
			if (isset($this->registered_ajax_validators[$name]))
				throw new \Exception("cinnabar ajax validator '$name' is registered twice");

			$this->registered_ajax_validators[$name] = $callback;
		}
	}



	public function enqueue_scripts()
	{
		wp_enqueue_script('jquery');

		wp_enqueue_script('cinnabar-ajax-helper', plugin_dir_url(__FILE__) . 'AjaxGatewayManager/cinnabar-ajax-helper.js');
		$ajax_helper_args = array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('cinnabar-ajax-nonce'),
		);
		wp_localize_script('cinnabar-ajax-helper', 'cinnabar_ajax_config', $ajax_helper_args);
	}

	public function cinnabar_action_gateway()
	{
		// error_log("debug start cinnabar ajax"); // DEBUG AJAX
		if (!$this->is_no_nonce_action((string)$_POST['cinnabar_action']) && wp_verify_nonce((string)$_POST['nonce'], 'cinnabar-ajax-nonce') === false)
			$res = array(
				'status' => 'error',
				'error' => 'invalid nonce: please refresh the page',
			);
		elseif (!isset($_POST['cinnabar_action']) || !$this->is_valid_action((string)$_POST['cinnabar_action']))
			$res = array(
				'status' => 'error',
				'error' => 'invalid ajax action: please refresh the page',
			);
		else
		{
			$action = (string)$_POST['cinnabar_action'];
			$data = json_decode(stripslashes($_POST['data']), true);
			$data['current_user'] = wp_get_current_user()->ID;

			try {
				$this->app->do_plugin_action('check_permissions_ajax_cinnabar_action', array($action));
			} catch (\Exception $e) {
				$res = array(
					'status' => 'error',
					'error' => 'Permission Error: ' . $e->getMessage(),
				);
			}

			try {
				$data = $this->validate_cinnabar_action($action, $data);
			} catch (\Exception $e) {
				$res = array(
					'status' => 'error',
					'error' => 'Validation Error: ' . $e->getMessage(),
				);
			}
			if (!isset($res))
				$res = $this->perform_cinnabar_action($action, $data);
		}

		echo json_encode($res + array( 'nonce' => wp_create_nonce('cinnabar-ajax-nonce') ));
		exit;
	}

	public function is_valid_action($action) {
		return isset($this->registered_ajax_actions[$action]);
	}

	public function is_no_nonce_action($action) {
		return isset($this->registered_ajax_actions[$action])
			&& isset($this->registered_ajax_actions[$action]['no_nonce'])
			&& $this->registered_ajax_actions[$action]['no_nonce'] === true;
	}

	public function validate_cinnabar_action($action, $data)
	{
		// error_log("debug ajax action ${action_page}__action_$action"); // DEBUG AJAX
		if (isset($this->registered_ajax_actions[$action]['validate']))
		{
			foreach ($this->registered_ajax_actions[$action]['validate'] as $argument => $validators)
			{
				if (!isset($data[$argument]))
					throw new \Exception("Missing argument: $argument");
				foreach ($validators as $validator => $args)
				{
					if (!isset($this->registered_ajax_validators[$validator]))
						throw new \Exception("Invalid validator '$validator' specified in ajax action '$action'");
					$data[$argument] = $this->registered_ajax_validators[$validator]($data, $data[$argument], $args);
				}
			}
		}

		return $data;
	}

	public function perform_cinnabar_action($action, $data)
	{
		// error_log("debug ajax action ${action_page}__action_$action"); // DEBUG AJAX
		return $this->registered_ajax_actions[$action]['callback']($data);
	}




	// default builtin validators
	public static function is_logged_in_validator($data, $value, $args)
	{
		if ($data['current_user'] === 0)
			throw new \Exception("Please log in");
		return $value;
	}

	public static function not_null_validator($data, $value, $args)
	{
		if ($value === null)
			throw new \Exception("invalid argument");
		return $value;
	}

	public static function cast_bool($data, $value, $args)
	{
		return (bool)$value;
	}

	public static function cast_int($data, $value, $args)
	{
		return (int)$value;
	}

	public static function cast_string($data, $value, $args)
	{
		return (string)$value;
	}

	public static function string_regex_validator($data, $value, $args)
	{
		$value = (string)$value;
		if (preg_match($args['regex'], $value) !== 1)
			throw new \Exception(isset($args['error']) ? $args['error'] : "invalid value");
		return $value;
	}

	public static function cast_bool_array($data, $value, $args)
	{
		return is_array($value) ? array_map(function($v) { return (bool)$v; }, $value) : array();
	}

	public static function cast_int_array($data, $value, $args)
	{
		return is_array($value) ? array_map(function($v) { return (int)$v; }, $value) : array();
	}

	public static function cast_string_array($data, $value, $args)
	{
		return is_array($value) ? array_map(function($v) { return (string)$v; }, $value) : array();
	}

	public static function cast_array($data, $value, $args)
	{
		return is_array($value) ? $value : array();
	}

	public static function parse_json($data, $value, $args)
	{
		return json_decode($value, true);
	}
}


