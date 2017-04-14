<?php


namespace Cinnabar;

class ViewController
{
	public function __construct($app, $page)
	{
		$this->app = $app;
		$this->page = $page;
	}

	public function template_redirect()
	{
		// example:
		// $this->app->redirect("my/page");
	}

	public function js_args()
	{
		// example:
		// return array('my-specific-js' => array('variable' => 'my_specific_args', 'args' => array('key' => 'value')));
		return array();
	}

	public function template_args()
	{
		// example:
		// return array('key' => 'value');
		return array();
	}

	public function template_title()
	{
		// used when no static title is specified in the synthetic page
		// example:
		// return "my awesome title";
		return $this->page['path'];
	}
}


