<?php


namespace Cinnabar\Mixin\SyntheticPageManager;

class ViewController
{
	public function __construct($app, $page)
	{
		$this->app = $app;
		$this->page = $page;
	}

	// load any models or data you need here
	public function template_action()
	{
		// example:
		// $this->user = MyUserModel->get_by_id(get_current_user_id());
	}

	// if the user needs to be redirected, it is best to do it here
	public function template_redirect()
	{
		// example:
		// $this->app->redirect("my/page");
	}

	// returns arguments passed to the js files
	public function js_args()
	{
		// example:
		// return array('my-specific-js' => array('variable' => 'my_specific_args', 'args' => array('key' => 'value')));
		return array();
	}

	// returns the arguments that are passed to the template
	public function template_args()
	{
		// example:
		// return array('key' => 'value');
		return array();
	}

	// used when no static title is specified in the synthetic page
	// if you need a dynamic title, return it from this method
	public function template_title()
	{
		// example:
		// return "my awesome title";
		return $this->page['path'];
	}
}


