<?php



class TestPluginPostModel extends Cinnabar\CustomPostModel
{
	public static $config = array(
		'post_type' => 'test_plugin_post',

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

		'virtual_fields' => array(
			'url' => 'post_url',
			// 'my_virtual_field' => <callback>(),
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
		'registration_properties' => array(
			'labels' => array(
				'name' => 'Test Plugin Posts',
				'singular_name' => 'Test Plugin Post',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Test Plugin Post',
				'edit_item' => 'Edit Test Plugin Posts',
				'new_item' => 'New Test Plugin Post',
				'view_item' => 'View Test Plugin Post',
				'search_items' => 'Search Test Plugin Posts',
				'not_found' => 'No Test Plugin Posts found',
				'not_found_in_trash' => 'No Test Plugin Posts found in Trash',
				'parent_item_colon' => 'Parent Test Plugin Post:',
				'menu_name' => 'Test Plugin Posts',
			),
			'hierarchical' => false,
			'description' => 'Test Plugin Posts',
			'supports' => array( 'title', 'page-attributes' ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			// 'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array('slug' => false),
			'capability_type' => 'page'
		),
	);

	public function post_url()
	{
		return site_url() . '/test_post_view/' . $this->id;
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


