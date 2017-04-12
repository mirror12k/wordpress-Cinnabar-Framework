<?php



class CustomPostModel
{
	// public static $config = array(
	// 	'post_type' => 'my_custom_post',
	// 	'slug_prefix' => '',
	// 	'custom_url_callback' => array('MyCustomPostModel', 'custom_url'),
	// 	'fields' => array(
	// 		'my_custom_field' => array(
	// 			'type' => 'meta',
	// 		),
	// 		'my_custom_field_with_description' => array(
	// 			'type' => 'meta',
	// 			'description' => 'my custom field #2',
	// 		),
	// 	),
	// );

	public static $default_wordpress_fields = array(
		'id' => 'ID',
		'author' => 'post_author',
		'parent' => 'post_parent',
		'slug' => 'post_name',
		'type' => 'post_type',
		'title' => 'post_title',
		'date' => 'post_date',
		'date_gmt' => 'post_date_gmt',
		'content' => 'post_content',
		'excerpt' => 'post_excerpt',
		'status' => 'post_status',
		'comment_status' => 'comment_status',
		'ping_status' => 'ping_status',
		'password' => 'post_password',
		'modified' => 'post_modified',
		'modified_gmt' => 'post_modified_gmt',
		'comment_count' => 'comment_count',
		'menu_order' => 'menu_order',
	);

	public $post;

	public function __construct($post)
	{
		$this->post = $post;
	}

	public function __isset($name)
	{
		return array_key_exists($name, CustomPostModel::$default_wordpress_fields)
				|| array_key_exists($name, static::$config['fields']);
	}

	public function __get($name)
	{
		if (isset(CustomPostModel::$default_wordpress_fields[$name]))
		{
			$field = CustomPostModel::$default_wordpress_fields[$name];
			return $this->post->$field;
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				return get_post_meta($this->post->ID, $name, true);
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				return get_post_meta($this->post->ID, $name, false);
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', from object type " . static::$config['post_type']);
		}
		else
			throw new \Exception("Unknown property '$name' requested, from object type " . static::$config['post_type']);
	}

	// public function __set($name, $value)
	// {
		
	// }

	public static function from_post($post)
	{
		return new static($post);
	}

	public static function get_by_id($id)
	{
		// error_log("debug get_by_id: $id");
		$post = get_post((int)$id);

		if ($post === null)
			return null;
		if ($post->post_type !== static::$config['post_type'])
			return null;

		return static::from_post($post);
	}

	public static function get_by_slug($team_type, $slug, $args=array())
	{
		$args = array_merge($args, array(
			// 'post_status' => array('publish', 'pending'),
			'name' => static::$config['slug_prefix'] . (string)$slug,
			'post_type' => static::$config['post_type'],
			'posts_per_page' => 1,
		));

		$query = new WP_Query($args);
		if ($query->have_posts())
			return static::from_post($query->post);
		else
			return null;
	}

	public static function list_posts($args=array())
	{
		$args['post_type'] = static::$config['post_type'];
		if (!isset($args['posts_per_page']))
			$args['posts_per_page'] = -1;

		$query = new WP_Query($args);
		$posts = array();
		foreach ($query->posts as $post)
			$posts[] = static::from_post($post);

		return $posts;
	}

	public static function search_posts($name, $count=-1, $args=array())
	{
		$args['s'] = $name;
		$args['posts_per_page'] = $count;
		
		return static::list_posts($args);
	}
}



