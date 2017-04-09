<?php




require_once 'TestViewController.php';

class TestPlugin extends Cinnabar\BasePlugin
{
	public $plugin_name = 'test-plugin';

	public $mixins = array(
		'SyntheticPageManager' => 'Cinnabar\\SyntheticPageManager',
		'UpdateTriggerManager' => 'Cinnabar\\UpdateTriggerManager',
		'EmailManager' => 'Cinnabar\\EmailManager',
	);

	// required for UpdateTriggerManager
	public $version_history = array(
		'0.0.1',
		'0.0.2',
	);


	public function register()
	{
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

		$this->UpdateTriggerManager->on_plugin_version('0.0.2', array($this, 'hello_world'));
	}

	public function wordpress_loaded()
	{
		$data = $this->EmailManager->render_email('TestPlugin/templates/test.twig', array('name' => 'John Doe'));
		error_log("debug EmailManager: " . json_encode($data));
	}

	public function hello_world()
	{
		error_log("hello world from v0.0.2!");
	}
}


