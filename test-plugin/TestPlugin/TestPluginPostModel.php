<?php



class TestPluginPostModel extends CustomPostModel
{
	public static $config = array(
		'post_type' => 'test_plugin_post',
		'fields' => array(
			'my_custom_field' => array(
				'type' => 'meta',
			),
		),
	);
}


