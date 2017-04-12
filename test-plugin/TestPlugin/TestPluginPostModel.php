<?php



class TestPluginPostModel extends CustomPostModel
{
	public static $config = array(
		'post_type' => 'test_plugin_post',
		'custom_url_callback' => array('TestPluginPostModel', 'custom_url'),
		'fields' => array(
			'my_custom_field' => array(
				'type' => 'meta',
			),
			'my_custom_bool' => array(
				'type' => 'meta',
				'cast' => 'bool',
			),
			'my_custom_int' => array(
				'type' => 'meta',
				'cast' => 'int',
			),
			'my_custom_string' => array(
				'type' => 'meta',
				'cast' => 'string',
			),
			'my_custom_option' => array(
				'type' => 'meta',
				'cast' => 'option',
				'option_values' => array('red', 'blue', 'grey'),
			),
			'my_custom_json' => array(
				'type' => 'meta',
				'cast' => 'json',
			),
		),
	);

	public static function custom_url($post)
	{
		return site_url() . '/test_post_view/' . $post->id;
	}
}


