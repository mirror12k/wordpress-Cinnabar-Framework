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
				|| array_key_exists($name, static::$config['fields'])
				|| array_key_exists($name, static::$config['virtual_fields']);
	}

	public function __get($name)
	{
		// error_log("debug __get $name"); // DEBUG GETSET
		if (isset(static::$default_wordpress_user_fields[$name]))
		{
			$field = static::$default_wordpress_user_fields[$name];
			if ($name === 'slug') {
				$value = $this->userdata->$field;
				// error_log("debug __get $name: $value"); // DEBUG GETSET
				if (substr($value, 0, strlen(static::$config['slug_prefix'])) === static::$config['slug_prefix'])
					return substr($value, strlen(static::$config['slug_prefix']));
				else
					return '';
			}
			else
			{
				return $this->userdata->$field;
			}
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				$value = get_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, true);
				// error_log("debug __get $name: $value"); // DEBUG GETSET

				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_from_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				return $value;
			}
			elseif (static::$config['fields'][$name]['type'] === 'meta-array')
			{
				$value_array = get_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, false);
				// error_log("debug __get $name: " . json_encode($value_array)); // DEBUG GETSET

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
		elseif (isset(static::$config['virtual_fields'][$name]))
		{
			$callback = static::$config['virtual_fields'][$name];
			return $this->$callback();
		}
		else
			throw new \Exception("Attempt to get unknown property '$name', from user type " . static::$config['user_type']);
	}

	public function __set($name, $value)
	{
		// error_log("debug __set $name"); // DEBUG GETSET
		if (isset(static::$default_wordpress_user_fields[$name]))
		{
			$field = static::$default_wordpress_user_fields[$name];
			// $this->userdata->$field = $value;

			if ($name === 'slug')
				$value = static::$config['slug_prefix'] . (string)$value;

			// error_log("debug __set $name: $value"); // DEBUG GETSET
			wp_update_user(array(
				'ID' => (int)$this->userdata->ID,
				$field => (string)$value,
			));
		}
		elseif (isset(static::$config['fields'][$name]))
		{
			if (static::$config['fields'][$name]['type'] === 'meta')
			{
				if (isset(static::$config['fields'][$name]['cast']))
					$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);

				// error_log("debug __set $name: $value"); // DEBUG GETSET
				update_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, $value);
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

				delete_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name);

				foreach ($value_array as $value)
				{
					// error_log("debug __set $name: $value"); // DEBUG GETSET
					add_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, $value, false);
				}

				return $value_array;
			}
			else
				throw new \Exception("Invalid CPM field type for '$name', to user type " . static::$config['user_type']);
		}
		elseif (isset(static::$config['virtual_fields'][$name]))
			throw new \Exception("Attempt to set virtual property '$name', to user type " . static::$config['user_type']);
		else
			throw new \Exception("Attempt to set unknown property '$name', to user type " . static::$config['user_type']);
	}

	public function add($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			// error_log("debug add $name: $value"); // DEBUG GETSET
			add_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, $value, false);
		}
		else
			throw new \Exception("Attempt to add invalid property value '$name', to user type " . static::$config['user_type']);

		// static::$manager->do_cpm_action(get_called_class(), 'changed__' . $name, array($this));
		// static::$manager->do_cpm_action(get_called_class(), 'added__' . $name, array($this, $value));
	}

	public function remove($name, $value)
	{
		if (isset(static::$config['fields'][$name]) && static::$config['fields'][$name]['type'] === 'meta-array')
		{
			if (isset(static::$config['fields'][$name]['cast']))
				$value = static::cast_value_to_string(static::$config['fields'][$name]['cast'], $value, static::$config['fields'][$name]);
			// error_log("debug remove $name: $value"); // DEBUG GETSET
			delete_user_meta($this->userdata->ID, static::$config['user_type'] . '__' . $name, $value);
		}
		else
			throw new \Exception("Attempt to remove invalid property value '$name', to user type " . static::$config['user_type']);

		// static::$manager->do_cpm_action(get_called_class(), 'changed__' . $name, array($this));
		// static::$manager->do_cpm_action(get_called_class(), 'removed__' . $name, array($this, $value));
	}


			// error_log("debug add $name: $value"); // DEBUG GETSET
			// error_log("debug remove $name: $value"); // DEBUG GETSET

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

	public function login_user()
	{
		wp_set_auth_cookie($this->id, false, is_ssl());
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
		if (!static::is_user_of_type($userdata->ID))
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
		$userdata = get_user_by('id', (int)$userid);
		$user = static::from_userdata($userdata);

		// mark him as a user of this custom model type
		update_user_meta((int)$userid, 'custom_user_model__user_type', static::$config['user_type']);

		// set meta fields
		foreach ($meta_args as $name => $value)
			$user->$name = $value;

		return $user;
	}


	public static function list_users($args=array())
	{
		if (!isset($args['meta_query']))
		{
			$args['meta_query'] = array(
				array(
					'key' => 'custom_user_model__user_type',
					'value' => static::$config['user_type'],
				),
			);
		}
		else
		{
			$args['meta_query'] = array(
				'relation' => 'AND',
				$args['meta_query'],
				array(
					'key' => 'custom_user_model__user_type',
					'value' => static::$config['user_type'],
				),
			);
		}

		if (!isset($args['number']))
			$args['number'] = -1;

		// error_log("got list_users request: " . json_encode($args));
		$query = new \WP_User_Query($args);
		$users = array();
		foreach ($query->get_results() as $userdata)
		{
			// error_log("got user: " . $userdata->ID);
			$users[] = static::from_userdata($userdata);
		}

		return $users;
	}

	public static function search_users($name, $count=-1, $args=array())
	{
		$args['search'] = "*$name*";
		$args['number'] = $count;
		$args['search_columns'] = array('nickname');
		
		return static::list_users($args);
	}

	public static function get_by($field, $value, $args=array())
	{
		if (!isset($args['meta_query']))
		{
			$args['meta_query'] = array(
				array(
					'key' => static::$config['user_type'] . '__' . $field,
					'value' => $value,
				),
			);
		}
		else
		{
			$args['meta_query'][] = array(
				'key' => static::$config['user_type'] . '__' . $field,
				'value' => $value,
			);
		}
		
		$users = static::list_users($args);
		if (count($users) > 0)
			return $users[0];
		else
			return null;
	}
}


