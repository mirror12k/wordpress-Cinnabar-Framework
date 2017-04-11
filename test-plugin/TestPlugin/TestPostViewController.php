<?php

class TestPostViewController extends Cinnabar\ViewController
{
	public function __construct($app, $page)
	{
		parent::__construct($app, $page);

		global $wp_query;
		if (isset($wp_query->query_vars['test_post_id']))
			$this->post = TestPluginPostModel::get_by_id($wp_query->query_vars['test_post_id']);
		else
			$this->post = null;
	}

	public function template_redirect()
	{
		if ($this->post === null)
			$this->app->redirect_home();
	}

	public function template_args()
	{
		return array('post' => $this->post);
	}
}

