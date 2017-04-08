<?php
/*
  Plugin Name: Test Plugin
  Plugin URI:
  Description: ?
  Author: mirror12k
  Version: 0.0.1
  Author URI: http://www.www.www/
*/

if (!defined('ABSPATH')) die('indirect access');

require_once 'ViewController.php';
require_once 'BasePlugin.php';
require_once 'BasePluginMixin.php';
require_once 'mixins/SyntheticPageManager/SyntheticPageManager.php';
require_once 'TestViewController.php';



class TestPlugin extends BasePlugin
{
	public $plugin_name = 'test-plugin';

	public $mixins = array(
		'SyntheticPageManager'
	);


	public function register()
	{
		$this->SyntheticPageManager->register_synthetic_pages(array(
			'synth-1' => array(
				'rewrite_rules' => array('doge-\d+/?$' => 'index.php?synthetic_page={{path}}')
			),
			'synth-2' => array(
				'view_controller' => 'TestViewController',
				'title' => 'my static title',
			),
			'synth-2/test-child' => array(
				'view_controller' => 'TestViewController',
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
	}
}

global $test_plugin;
$test_plugin = new TestPlugin();
$test_plugin->load_plugin();



