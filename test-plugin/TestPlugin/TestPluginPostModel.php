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
		),
	);

	public static function custom_url($post)
	{
		return site_url() . '/test_post_view/' . $post->id;
	}
}


