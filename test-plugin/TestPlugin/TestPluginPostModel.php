<?php



class TestPluginPostModel extends CustomPostModel
{
	public static $config = array(
		'post_type' => 'test_plugin_post',
		'custom_url_callback' => array('TestPluginPostModel', 'custom_url'),
		'fields' => array(
			'my_custom_field' => array(
				'type' => 'meta',
				'description' => 'My Custom Field',
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
				'option_values' => array('red' => 'Red', 'blue' => 'Blue', 'grey' => 'Grey'),
			),
			'my_custom_json' => array(
				'type' => 'meta',
				'cast' => 'json',
			),
			'my_custom_int_array' => array(
				'type' => 'meta-array',
				'cast' => 'int',
			),
		),
		'field_groups' => array(
			'actions' => array(
				// 'fields' => array('my_custom_field'),
				'title' => 'My Actions',
				'render_callback' => array('TestPluginPostModel', 'render_actions'),
			),
			'fields' => array(
				'fields' => array('my_custom_field', 'my_custom_bool', 'my_custom_int', 'my_custom_string', 'my_custom_option', 'my_custom_json', 'my_custom_int_array'),
				'title' => 'Properties',
				// 'render_callback' => array('TestPluginPostModel', 'render_actions'),
			),
		),
	);

	public static function custom_url($post)
	{
		return site_url() . '/test_post_view/' . $post->id;
	}

	public static function render_actions($cpm, $post, $field_group)
	{

		?>
		<table class="form-table">
		<h1>Hello World!</h1>

		<div class="cinnabar_action_form" data-ajax-action="test-post-callback">
			<input type="hidden" name="postid" value="<?php echo htmlspecialchars($post->id); ?>" />
			<button class="frm_button submitter">Awesome Callback</button>
		</div>

		</table>
		<?php
	}
}


