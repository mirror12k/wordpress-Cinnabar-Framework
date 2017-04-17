<?php



namespace Cinnabar;

class InputHelperLibrary extends BasePluginMixin
{

	public $registered_dynamic_input_callbacks = array();

	public function register()
	{
		$this->app->register_admin_scripts(array(
			'jquery' => null,
			'input-helper-library' => 'Cinnabar/mixins/InputHelperLibrary/input-helper-library.js',
		));

		$this->app->AjaxGatewayManager->register_ajax_actions(array(
			'input_helper_library__dynamic_input_query' => array(
				'callback' => array($this, 'input_helper_library__dynamic_input_query'),
				'validate' => array(
					// 'current_user' => array('is_logged_in_validator'),
					'dynamic_input_name' => array('cast_string'),
					'query' => array('cast_string'),
				),
			),
		));
	}

	public function register_dynamic_input_callbacks($callbacks)
	{
		foreach ($callbacks as $dynamic_input_name => $callback)
		{
			if (isset($this->registered_dynamic_input_callbacks[$dynamic_input_name]))
				throw new \Exception("dynamic input callback '$dynamic_input_name' is registered twice");

			$this->registered_dynamic_input_callbacks[$dynamic_input_name] = $callback;
		}
	}

	public function input_helper_library__dynamic_input_query($args)
	{
		$dynamic_input_name = $args['dynamic_input_name'];
		$query = $args['query'];

		if (isset($this->registered_dynamic_input_callbacks[$dynamic_input_name]))
		{
			$callback = $this->registered_dynamic_input_callbacks[$dynamic_input_name];
			$data = $callback($query);
			return array('status' => 'success', 'data' => $data);
		}
		else
		{
			return array('status' => 'error', 'error' => 'invalid dynamic_input_name');
		}

	}

}


