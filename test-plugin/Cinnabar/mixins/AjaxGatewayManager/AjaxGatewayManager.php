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

		wp_enqueue_script('cinnabar-ajax-helper', $this->app->plugin_url('/Cinnabar/mixins/AjaxGatewayManager/include.js'));
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
				foreach ($validators as $validator)
				{
					$data[$argument] = $this->registered_ajax_validators[$validator]($data, $data[$argument]);
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
}

