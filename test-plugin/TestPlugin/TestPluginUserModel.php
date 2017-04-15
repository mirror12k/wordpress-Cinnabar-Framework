<?php



class TestPluginUserModel extends Cinnabar\CustomUserModel
{
	public static $config = array(
		'user_type' => 'test_plugin_user_type',
		'slug_prefix' => '',
		'default_role' => 'Subscriber',

		'fields' => array(
			'my_custom_user_field' => array(
				'type' => 'meta',
				// 'cast' => 'int',
				// 'default' => '15',
				// 'description' => 'my custom field #2',
			),
		),

		// 'custom_cast_types' => array(
		// 	'my_cast' => array(
		// 		'from_string' => <callback>($value, $field),
		// 		'to_string' => <callback>($value, $field),
		// 		'render_input' => <callback>($field, $input_name, $value, $is_template),
		// 	),
		// ),
	);
}


