<?php



namespace Cinnabar;

class AjaxGatewayManager extends BasePluginMixin
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
			'is_logged_in_validator' => array('Cinnabar\\AjaxGatewayManager', 'is_logged_in_validator'),
			'not_null_validator' => array('Cinnabar\\AjaxGatewayManager', 'not_null_validator'),
			'cast_bool' => array('Cinnabar\\AjaxGatewayManager', 'cast_bool'),
			'cast_int' => array('Cinnabar\\AjaxGatewayManager', 'cast_int'),
			'cast_string' => array('Cinnabar\\AjaxGatewayManager', 'cast_string'),
			'parse_json' => array('Cinnabar\\AjaxGatewayManager', 'parse_json'),
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

		wp_enqueue_script('cinnabar-ajax-helper', $this->app->plugin_url('/Cinnabar/mixins/AjaxGatewayManager/cinnabar-ajax-helper.js'));
		$ajax_helper_args = array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('cinnabar-ajax-nonce'),
		);
		wp_localize_script('cinnabar-ajax-helper', 'cinnabar_ajax_config', $ajax_helper_args);
	}

	public function cinnabar_action_gateway()
	{
		// error_log("debug start cinnabar ajax"); // DEBUG AJAX
		if (wp_verify_nonce((string)$_POST['nonce'], 'cinnabar-ajax-nonce') === false)
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
			$data = $_POST['data'];
			$data['current_user'] = wp_get_current_user()->ID;

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

	public function is_valid_action($action)
	{
		return isset($this->registered_ajax_actions[$action]);
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

	public static function parse_json($data, $value, $args)
	{
		return json_decode($value);
	}
}


