<?php


namespace Cinnabar\Mixin\SyntheticPageManager;

class ItemListViewController extends \Cinnabar\Mixin\SyntheticPageManager\ViewController
{
	// public static $config = array(
	// 	'model_class' => 'MyCustomPostModel',
	// 	'index_field' => 'page_index',
	// 	'items_key' => 'my_posts',
	// 	'url_prefix' => '/items/',
	// 	'posts_per_page' => 10,
	// );

	public function template_action() {

		global $wp_query;
		if (isset($wp_query->query_vars[static::$config['index_field']]))
			$this->page_index = (int)$wp_query->query_vars[static::$config['index_field']];
		else
			$this->page_index = 1;

		$count = static::$config['posts_per_page'];
		$get_call = array(static::$config['model_class'], 'list_posts');
		$this->posts_list = $get_call(array(
			'post_status' => 'publish',
			'posts_per_page' => $count,
			'offset' => ($this->page_index - 1) * $count,
		));

		if ($this->page_index > 1)
			$this->previous_link = $this->app->site_url(static::$config['url_prefix'] . ($this->page_index - 1));
		else
			$this->previous_link = null;
		if (count($this->posts_list) === $count)
			$this->next_link = $this->app->site_url(static::$config['url_prefix'] . ($this->page_index + 1));
		else
			$this->next_link = null;
	}

	public function template_args()
	{
		$args = array(
			'page_index' => $this->page_index,
			'previous_link' => $this->previous_link,
			'next_link' => $this->next_link,
		);
		$args[static::$config['items_key']] = $this->posts_list;
		return $args;
	}
}


