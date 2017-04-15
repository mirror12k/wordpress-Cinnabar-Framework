<?php



namespace Cinnabar;

class CustomUserModel
{
	// public static $config = array(
	// 	'user_type' => 'my_custom_user_type',
	// 	'slug_prefix' => '',
	// 	'default_role' => 'Subscriber',

	// 	'fields' => array(
	// 		'my_custom_field' => array(
	// 			'type' => 'meta',
	// 			// 'cast' => 'int',
	// 			// 'default' => '15',
	// 			// 'description' => 'my custom field #2',
	// 		),
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
	// 	// 		// 'render_callback' => <callback>($custom_user_manager, $user, $field_group),
	// 	// 	)
	// 	// ),
	// );

	public static $default_wordpress_user_fields = array(
		'id' => 'ID',
		'login' => 'user_login',
		'pass' => 'user_pass',
		'slug' => 'user_nicename',
		'email' => 'user_email',
		'user_url' => 'user_url',
		'user_registered' => 'user_registered',
		'display_name' => 'display_name',

		'first_name' => 'first_name',
		'last_name' => 'last_name',
		'nickname' => 'nickname',
		'description' => 'description',
		'wp_capabilities' => 'wp_capabilities',
		'admin_color' => 'admin_color',
		'closedpostboxes_page' => 'closedpostboxes_page',
		'primary_blog' => 'primary_blog',
		'rich_editing' => 'rich_editing',
		'source_domain' => 'source_domain',

	);



	public $userdata;

	public function __construct($userdata)
	{
		$this->userdata = $userdata;
	}

	public function __isset($name)
	{
		return array_key_exists($name, static::$default_wordpress_user_fields)
				|| array_key_exists($name, static::$config['fields']);
	}

	public function __get($name)
	{
		if (isset(static::$default_wordpress_user_fields[$name]))
		{
			$field = static::$default_wordpress_user_fields[$name];
			return $this->userdata->$field;
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				$value = get_user_meta($this->userdata->ID, $name, true);

				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				return $value;
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				$value_array = get_user_meta($this->userdata->ID, $name, false);

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
				throw new \Exception("Invalid CPM field type for '$name', from user type " . static::$config['user_type']);
		}
		else
			throw new \Exception("Attempt to get unknown property '$name', from user type " . static::$config['user_type']);
	}

	public function __set($name, $value)
	{
		if (isset(static::$default_wordpress_user_fields[$name]))
		{
			$field = static::$default_wordpress_user_fields[$name];
			$this->userdata->$field = $value;
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				update_user_meta($this->userdata->ID, $name, $value);
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

				delete_user_meta($this->userdata->ID, $name);

				foreach ($value_array as $value)
					add_user_meta($this->userdata->ID, $name, $value, false);

				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', to user type " . static::$config['user_type']);
		}
		else
			throw new \Exception("Attempt to set unknown property '$name', to user type " . static::$config['user_type']);
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
			throw new \Exception("Unknown cast type '$cast_type' requested, from user type " . static::$config['user_type']);
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
			throw new \Exception("Unknown cast type '$cast_type' requested, from user type " . static::$config['user_type']);
	}



	public static function from_userdata($userdata)
	{
		return new static($userdata);
	}

	public static function get_user_type($userid)
	{
		return get_user_meta((int)$userid, 'custom_user_model__user_type', true);
	}

	public static function is_user_of_type($userid)
	{
		return static::$config['user_type'] === static::get_user_type($userid);
	}

	public static function get_by_id($userid)
	{
		$userdata = get_user_by('id', (int)$userid);
		if ($userdata === false)
			return null;
		if (!static::is_user_of_type($userid))
			return null;
		
		return static::from_userdata($userdata);
	}

	public static function get_by_slug($slug)
	{
		$userdata = get_user_by('slug', static::$config['slug_prefix'] . (string)$slug);
		if ($userdata === false)
			return null;
		if (!static::is_user_of_type($userid))
			return null;
		
		return static::from_userdata($userdata);
	}


	public static function create($args)
	{
		$user_args = array();
		$meta_args = array();

		// parse the args into user_args and meta_args
		foreach ($args as $name => $value)
			// userdata arg
			if (isset(static::$default_wordpress_user_fields[$name]))
				$user_args[static::$default_wordpress_user_fields[$name]] = $value;
			// meta arg
			elseif (isset(static::$config['fields'][$name]))
			{
				// cast but don't save the value to make sure that it won't error AFTER we create the post
				if (isset(static::$config['fields'][$name]['cast']))
					static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
				$meta_args[$name] = $value;
			}
			else
				throw new \Exception("invalid CPM create argument: $name, for user type " . static::$config['user_type']);

		// set any defaults for basic user args
		if (isset(static::$config['default_user_args']))
			foreach (static::$config['default_user_args'] as $name => $value)
				if (isset(static::$default_wordpress_user_fields[$name]))
					$post_args[static::$default_wordpress_user_fields[$name]] = $value;
				else
					throw new \Exception("invalid default post argument '$name', for user type " . static::$config['user_type']);

		// set any defaults for meta fields
		foreach (static::$config['fields'] as $name => $field)
			if (isset($field['default']) && !isset($meta_args[$name]))
				$meta_args[$name] = $field['default'];

		// set a few necessary defaults
		$user_args['user_nicename'] = (isset($args['slug']) ? static::$config['slug_prefix'] . $args['slug'] : static::$config['slug_prefix'] . '-default');
		if (!isset($user_args['user_pass']))
			$user_args['user_pass'] = wp_generate_password(20, true);
		if (!isset($user_args['role']))
			$user_args['role'] = static::$config['default_role'];

		// create the user
		$result = wp_insert_user($user_args);
		if (is_wp_error($result))
			die("error creating " . static::$config['user_type'] . " user: " . $result->get_error_message());

		$userid = $result;
		$user = static::get_by_id($userid);

		// mark him as a user of this custom model type
		update_user_meta((int)$userid, 'custom_user_model__user_type', static::$config['user_type']);

		// set meta fields
		foreach ($meta_args as $name => $value)
			$user->$name = $value;

		return $user;
	}

}


