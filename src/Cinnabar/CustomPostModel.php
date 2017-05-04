<?php



namespace Cinnabar;

class CustomPostModel
{
	// public static $manager;

	// public static $config = array(
	// 	'post_type' => 'my_custom_post',
	// 	'slug_prefix' => '',

	// 	'fields' => array(
	// 		// 'my_custom_field' => array(
	// 		// 	'type' => 'meta',
	// 		// 	// 'cast' => 'int',
	// 		// 	// 'default' => '15',
	// 		// 	// 'description' => 'my custom field #2',
	// 		// ),
	// 	),

	// 	'virtual_fields' => array(
	// 		// 'url' => <callback>(),
	// 		// 'my_virtual_field' => <callback>(),
	// 	),

	// 	// 'custom_cast_types' => array(
	// 	// 	'my_cast' => array(
	// 	// 		'from_string' => <callback>($value, $field),
	// 	// 		'to_string' => <callback>($value, $field),
	// 	// 		'render_input' => <callback>($field, $input_name, $value, $is_template),
	// 	// 	),
	// 	// ),

	// 	// 'field_groups' => array(
	// 	// 	'tag' => array(
	// 	// 		'fields' => array('my_custom_field'),
	// 	// 		'title' => 'My Favorite Fields',
	// 	// 		// 'render_callback' => <callback>($custom_post_manager, $post, $field_group),
	// 	// 	)
	// 	// ),

	// 	'registration_properties' => array(
	// 		'labels' => array(
	// 			'name' => __( 'My Special Posts', 'my_custom_post' ),
	// 			'singular_name' => __( 'My Special Post', 'my_custom_post' ),
	// 			'add_new' => __( 'Add New', 'my_custom_post' ),
	// 			'add_new_item' => __( 'Add New My Special Post', 'my_custom_post' ),
	// 			'edit_item' => __( 'Edit My Special Posts', 'my_custom_post' ),
	// 			'new_item' => __( 'New My Special Post', 'my_custom_post' ),
	// 			'view_item' => __( 'View My Special Post', 'my_custom_post' ),
	// 			'search_items' => __( 'Search My Special Posts', 'my_custom_post' ),
	// 			'not_found' => __( 'No My Special Posts found', 'my_custom_post' ),
	// 			'not_found_in_trash' => __( 'No My Special Posts found in Trash', 'my_custom_post' ),
	// 			'parent_item_colon' => __( 'Parent My Special Post:', 'my_custom_post'),
	// 			'menu_name' => __( 'My Special Posts', 'my_custom_post' ),
	// 		),
	// 		'hierarchical' => false,
	// 		'description' => __( 'My Special Posts', 'my_custom_post' ),
	// 		'supports' => array( 'title', 'page-attributes' ),
	// 		'public' => true,
	// 		'show_ui' => true,
	// 		'show_in_menu' => true,
	// 		// 'show_in_nav_menus' => true,
	// 		'publicly_queryable' => true,
	// 		'exclude_from_search' => true,
	// 		'has_archive' => true,
	// 		'query_var' => true,
	// 		'can_export' => true,
	// 		'rewrite' => true,
	// 		'capability_type' => 'page'
	// 	),
	// );

	public static $default_wordpress_post_fields = array(
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
		return array_key_exists($name, static::$default_wordpress_post_fields)
				|| array_key_exists($name, static::$config['fields'])
				|| array_key_exists($name, static::$config['virtual_fields']);
	}

	public function __get($name)
	{
		if (isset(static::$default_wordpress_post_fields[$name]))
		{
			$field = static::$default_wordpress_post_fields[$name];
			if ($name === 'slug')
			{
				$value = $this->post->$field;
				if (strlen($value) > strlen(static::$config['slug_prefix']) && substr($value, 0, strlen(static::$config['slug_prefix'])) === static::$config['slug_prefix'])
					return substr($value, strlen(static::$config['slug_prefix']));
				else
					return '';
			}
			else
			{
				return $this->post->$field;
			}
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				$value = get_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, true);

				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				return $value;
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				$value_array = get_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, false);

				if (isset(static::$config['fields'][$name]['cast']))
				{
					$cast_array = array();
					foreach ($value_array as $value)
						$cast_array[] = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
					$value_array = $cast_array;
				}
				// error_log("debug __get: " . json_encode($value_array));
				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', from object type " . static::$config['post_type']);
		}
		elseif (isset(static::$config['virtual_fields'][$name]))
		{
			$callback = static::$config['virtual_fields'][$name];
			return $this->$callback();
		}
		else
			throw new \Exception("Attempt to get unknown property '$name', from object type " . static::$config['post_type']);
	}

	public function __set($name, $value)
	{
		if (isset(static::$default_wordpress_post_fields[$name]))
		{
			$field = static::$default_wordpress_post_fields[$name];

			if ($name === 'slug')
				$value = static::$config['slug_prefix'] . (string)$value;

			wp_update_post(array(
				'ID' => (int)$this->post->ID,
				$field => (string)$value,
			));
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				// error_log("debug update_post_meta for " . $this->post->ID . ' field ' . static::$config['post_type'] . '__' . $name);
				update_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, $value);
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

				delete_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name);

				foreach ($value_array as $value)
					add_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, $value, false);

				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', to object type " . static::$config['post_type']);
		}
		elseif (isset(static::$config['virtual_fields'][$name]))
			throw new \Exception("Attempt to set virtual property '$name', to object type " . static::$config['post_type']);
		else
			throw new \Exception("Attempt to set unknown property '$name', to object type " . static::$config['post_type']);

		static::$manager->do_cpm_action(get_called_class(), 'changed__' . $name, array($this));
	}

	public function add($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			add_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, $value, false);
		}
		else
			throw new \Exception("Attempt to add invalid property value '$name', to object type " . static::$config['post_type']);

		static::$manager->do_cpm_action(get_called_class(), 'changed__' . $name, array($this));
		static::$manager->do_cpm_action(get_called_class(), 'added__' . $name, array($this, $value));
	}

	public function remove($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			delete_post_meta($this->post->ID, static::$config['post_type'] . '__' . $name, $value);
		}
		else
			throw new \Exception("Attempt to remove invalid property value '$name', to object type " . static::$config['post_type']);

		static::$manager->do_cpm_action(get_called_class(), 'changed__' . $name, array($this));
		static::$manager->do_cpm_action(get_called_class(), 'removed__' . $name, array($this, $value));
	}

	public static function cast_value_from_string($cast_type, $value, $field, $class='')
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

	public static function cast_value_to_string($cast_type, $value, $field)
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

	public static function get_by_slug($slug, $args=array())
	{
		$args = array_merge($args, array(
			// 'post_status' => array('publish', 'pending'),
			'name' => static::$config['slug_prefix'] . (string)$slug,
			'post_type' => static::$config['post_type'],
			'posts_per_page' => 1,
		));

		$query = new \WP_Query($args);
		if ($query->have_posts())
			return static::from_post($query->post);
		else
			return null;
	}

	public static function create($args)
	{
		$post_args = array();
		$meta_args = array();

		// parse the given args into post_args and meta_args
		foreach ($args as $name => $value)
			// post arg
			if (isset(static::$default_wordpress_post_fields[$name]))
				$post_args[static::$default_wordpress_post_fields[$name]] = $value;
			// meta arg
			elseif (isset(static::$config['fields'][$name]))
			{
				// cast but don't save the value to make sure that it won't error AFTER we create the post
				if (isset(static::$config['fields'][$name]['cast']))
					static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
				$meta_args[$name] = $value;
			}
			else
				throw new \Exception("invalid CPM create argument: $name, for object type " . static::$config['post_type']);

		// set any defaults for basic post args
		if (isset(static::$config['default_post_args']))
			foreach (static::$config['default_post_args'] as $name => $value)
				if (isset(static::$default_wordpress_post_fields[$name]))
					$post_args[static::$default_wordpress_post_fields[$name]] = $value;
				else
					throw new \Exception("invalid default post argument '$name', for object type " . static::$config['post_type']);

		// set any defaults for meta fields
		foreach (static::$config['fields'] as $name => $field)
			if (isset($field['default']) && !isset($meta_args[$name]))
				$meta_args[$name] = $field['default'];

		// set a few necessary perliminaries
		$post_args['post_type'] = static::$config['post_type'];
		$post_args['post_name'] = (isset($args['slug']) ? static::$config['slug_prefix'] . $args['slug'] : static::$config['slug_prefix'] . '-default');
		if (!isset($post_args['post_status']))
			$post_args['post_status'] = 'publish';
		if (!isset($post_args['comment_status']))
			$post_args['comment_status'] = 'closed';

		// create the post
		$result = wp_insert_post($post_args, true);
		if (is_wp_error($result))
			die("error creating " . static::$config['post_type'] . " post: " . $result->get_error_message());

		$postid = $result;
		$post = static::get_by_id($postid);

		// set meta fields
		foreach ($meta_args as $name => $value)
			$post->$name = $value;

		// wp_set_object_terms($matchid, $terms, 'tfcl_match_type');

		static::$manager->do_cpm_action(get_called_class(), 'created', array($post));

		return $post;
	}

	array(
		'relation' => 'AND'
		'asdf' => 'qwerty'
	)

	public static function compile_meta_args($args)
	{
		$query = array();

		foreach ($args as $name => $value)
		{
			if ($name === 'relation')
				$query['relation'] = $value;
			elseif (is_array($value))
				$query[] = static::compile_meta_args($value);
			else
			{
				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				$query[] = array(
					'key' => static::$config['post_type'] . '__' . $name,
					'value' => $value,
				);
			}
		}
	}

	public static function list_posts($args=array())
	{
		$args['post_type'] = static::$config['post_type'];
		if (!isset($args['posts_per_page']))
			$args['posts_per_page'] = -1;

		if (isset($args['meta_args']))
		{
			$compiled_meta_args = static::compile_meta_args($args['meta_args']);
			unset($args['meta_args']);
			if (isset($args['meta_query']))
				$args['meta_query'] = array(
					'relation' => 'AND',
					$args['meta_query'],
					$compiled_meta_args,
				);
			else
				$args['meta_query'] = $compiled_meta_args;
		}


		// error_log("got list_posts request: " . json_encode($args));
		$query = new \WP_Query($args);
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

	public static function register($manager)
	{
		static::$manager = $manager;

		if (isset(static::$config['registration_properties']))
		{
			foreach (static::$config['registration_properties']['labels'] as $key => $text)
				static::$config['registration_properties']['labels'][$key] = __($text, static::$config['post_type']);
			static::$config['registration_properties']['description'] = __(static::$config['registration_properties']['description'], static::$config['post_type']);
			register_post_type(static::$config['post_type'], static::$config['registration_properties']);
		}

		add_action('transition_post_status', array(get_called_class(), 'on_all_status_transitions'), 10, 3);
	}

	public static function on_all_status_transitions($new_status, $old_status, $post) {
		// error_log("on_all_status_transitions: $old_status => $new_status : " . json_encode($post));
		if ($post->post_type === static::$config['post_type'] && $old_status === 'new')
			static::on_new_post($post);
	}

	public static function on_new_post($post)
	{
		// error_log("on_new_post for post type " . static::$config['post_type']);
		$post = static::from_post($post);

		foreach (static::$config['fields'] as $name => $field)
			if (isset($field['default']))
			{
				$post->$name = $field['default'];
			}

		static::$manager->do_cpm_action(get_called_class(), 'new', array($post));
	}
}



