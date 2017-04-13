<?php



class CustomPostModel
{
	// public static $config = array(
	// 	'post_type' => 'my_custom_post',
	// 	'slug_prefix' => '',
	// 	// 'custom_url_callback' => array('MyCustomPostModel', 'custom_url'),
	// 	'fields' => array(
	// 		'my_custom_field' => array(
	// 			'type' => 'meta',
	// 			// 'description' => 'my custom field #2',
	// 		),
	// 	),
	// 	// 'custom_cast_types' => array(
	// 	// 	'my_cast' => array(
	// 	// 		'from_string' => 'callback',
	// 	// 		'to_string' => 'callback',
	// 	// 	),
	// 	// )
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
				|| $name === 'url'
				|| array_key_exists($name, static::$config['fields']);
	}

	public function __get($name)
	{
		if (isset(CustomPostModel::$default_wordpress_fields[$name]))
		{
			$field = CustomPostModel::$default_wordpress_fields[$name];
			return $this->post->$field;
		}
		elseif ($name === 'url')
		{
			return get_permalink($this->post);
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				$value = get_post_meta($this->post->ID, $name, true);

				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				return $value;
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				$value_array = get_post_meta($this->post->ID, $name, false);

				if (isset(static::$config['fields'][$name]['cast']))
				{
					$cast_array = array();
					foreach ($value_array as $value)
						$cast_array[] = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
					$value_array = $cast_array;
				}

				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', from object type " . static::$config['post_type']);
		}
		else
			throw new \Exception("Attempt to get unknown property '$name', from object type " . static::$config['post_type']);
	}

	public function __set($name, $value)
	{
		if (isset(CustomPostModel::$default_wordpress_fields[$name]))
		{
			$field = CustomPostModel::$default_wordpress_fields[$name];
			$this->post->$field = $value;
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				update_post_meta($this->post->ID, $name, $value);
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				$value_array = $value;

				if (isset(static::$config['fields'][$name]['cast']))
				{
					$cast_array = array();
					foreach ($value_array as $value)
						$cast_array[] = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
					$value_array = $cast_array;
				}

				delete_post_meta($this->post->ID, $name);

				foreach ($value_array as $value)
					add_post_meta($this->post->ID, $name, $value, false);

				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', to object type " . static::$config['post_type']);
		}
		else
			throw new \Exception("Attempt to set unknown property '$name', to object type " . static::$config['post_type']);
	}

	public function add($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			add_post_meta($this->post->ID, $name, $value, false);
		}
		else
			throw new \Exception("Attempt to add invalid property value '$name', to object type " . static::$config['post_type']);
	}

	public function remove($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			delete_post_meta($this->post->ID, $name, $value);
		}
		else
			throw new \Exception("Attempt to remove invalid property value '$name', to object type " . static::$config['post_type']);
	}

	public function cast_value_from_string($cast_type, $value, $field)
	{
		if ($cast_type === 'bool')
			return (bool)$value;
		elseif ($cast_type === 'int')
			return (int)$value;
		elseif ($cast_type === 'float')
			return (float)$value;
		elseif ($cast_type === 'string')
			return (string)$value;
		elseif ($cast_type === 'option')
			return (string)$value;
		elseif ($cast_type === 'json')
			return json_decode($value);
		elseif (isset(static::$config['custom_cast_types']) && isset(static::$config['custom_cast_types'][$cast_type]))
		{
			$callback = static::$config['custom_cast_types'][$cast_type]['from_string'];
			return $callback($value, $field);
		}
		else
			throw new \Exception("Unknown cast type '$cast_type' requested, from object type " . static::$config['post_type']);
	}

	public function cast_value_to_string($cast_type, $value, $field)
	{
		if ($cast_type === 'bool')
			return (string)$value;
		elseif ($cast_type === 'int')
			return (string)$value;
		elseif ($cast_type === 'float')
			return (string)$value;
		elseif ($cast_type === 'string')
			return (string)$value;
		elseif ($cast_type === 'option')
		{
			if (array_key_exists((string)$value, $field['option_values']))
				return (string)$value;
			else
				return '';
		}
		elseif ($cast_type === 'json')
			return json_encode($value);
		elseif (isset(static::$config['custom_cast_types']) && isset(static::$config['custom_cast_types'][$cast_type]))
		{
			$callback = static::$config['custom_cast_types'][$cast_type]['to_string'];
			return $callback($value, $field);
		}
		else
			throw new \Exception("Unknown cast type '$cast_type' requested, from object type " . static::$config['post_type']);
	}

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



