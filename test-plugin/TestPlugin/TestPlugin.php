<?php




require_once 'TestViewController.php';

class TestPlugin extends Cinnabar\BasePlugin
{
	public $plugin_name = 'test-plugin';

	public $mixins = array(
		'SyntheticPageManager' => 'Cinnabar\\SyntheticPageManager',
		'UpdateTriggerManager' => 'Cinnabar\\UpdateTriggerManager',
		'EmailManager' => 'Cinnabar\\EmailManager',
		'AjaxGatewayManager' => 'Cinnabar\\AjaxGatewayManager',
	);

	// required for UpdateTriggerManager
	public $version_history = array(
		'0.0.1',
		'0.0.2',
	);


	public function register()
	{
		$this->register_plugin_options(array(
			'test-plugin-a1-settings' => array(
				'title' => 'Test Plugin A section',
				'fields' => array(
					'test-plugin-a1-test-field' => array(
						'label' => 'test plugin a test field',
					),
					'test-plugin-a1-test-bool' => array(
						'label' => 'test plugin a test bool',
						'option_type' => 'boolean',
					),
				),
			),
		));

		$this->SyntheticPageManager->register_synthetic_pages(array(
			'synth-1' => array(
				'rewrite_rules' => array('doge-\d+/?$' => 'index.php?synthetic_page={{path}}'),
				'template' => 'TestPlugin/templates/synth.twig',
			),
			'synth-2' => array(
				'view_controller' => 'TestViewController',
				'title' => 'my static title',
				'template' => 'TestPlugin/templates/synth.twig',
			),
			'synth-2/test-child' => array(
				'view_controller' => 'TestViewController',
				'template' => 'TestPlugin/templates/synth.twig',
			),
		));

		$this->AjaxGatewayManager->register_ajax_validators(array(
			'is_logged_in_validator' => array($this, 'is_logged_in_validator'),
			'intval_validator' => array($this, 'intval_validator'),
		));

		$this->AjaxGatewayManager->register_ajax_actions(array(
			'say-hello-world' => array(
				'callback' => array($this, 'ajax_hello_world'),
				'validate' => array(
					'current_user' => array('is_logged_in_validator'),
					'asdf' => array('intval_validator'),
				),
			),
		));

		$this->UpdateTriggerManager->on_plugin_version('0.0.2', array($this, 'hello_world'));
	}

	// public function wordpress_loaded()
	// {
	// 	$data = $this->EmailManager->render_email('TestPlugin/templates/test.twig', array('name' => 'John Doe'));
	// 	error_log("debug EmailManager: " . json_encode($data));
	// }

	public function is_logged_in_validator($data, $arg)
	{
		if ($data['current_user'] === 0)
			throw new Exception("Please log in");
		return $arg;
	}

	public function intval_validator($data, $arg)
	{
		return intval($arg);
	}

	public function hello_world()
	{
		error_log("hello world from v0.0.2!");
	}

	public function ajax_hello_world($args)
	{
		error_log("hello world from ajax!");
		return array('status' => 'success', 'data' => 'hello world from ajax gateway! i got an arg: ' . $args["asdf"]);
	}
}


