<?php



class CustomPostModel
{
	// public static $config = array(
	// 	'post_type' => 'my_custom_post',
	// 	'fields' => array(
	// 		'my_custom_field' => array(
	// 			'type' => 'meta',
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

	public static function get_by_id($id)
	{
		// error_log("debug get_by_id: $id");
		$post = get_post((int)$id);

		if ($post === null)
			return null;
		if ($post->post_type !== static::$config['post_type'])
			return null;

		return new static($post);
	}

}



