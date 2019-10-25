<?php


namespace Cinnabar\Mixin\SyntheticPageManager;

class ItemViewController extends \Cinnabar\Mixin\SyntheticPageManager\ViewController
{
	// public static $config = array(
	// 	'model_class' => 'MyCustomPostModel',
	// 	'id_field' => 'my_post_id',
	// 	'item_key' => 'my_post',
	// 	// 'redirect_on_missing' => 'my_post',
	// );

	public function template_action()
	{
		$get_call = array(static::$config['model_class'], 'get_by_id');

		global $wp_query;
		if (isset($wp_query->query_vars[static::$config['id_field']]))
			$this->item_instance = $get_call((int)$wp_query->query_vars[static::$config['id_field']]);
		else
			$this->item_instance = null;
	}

	// if the user needs to be redirected, it is best to do it here
	public function template_redirect()
	{
		// example:
		if ($this->item_instance === null && isset(static::$config['redirect_on_missing']))
			$this->app->redirect($this->app->site_url(static::$config['redirect_on_missing']));
	}

	// returns the arguments that are passed to the template
	public function template_args()
	{
		// example:
		// return array('key' => 'value');
		$args = array();
		$args[static::$config['item_key']] = $this->item_instance;
		return $args;
	}
}


