<?php
/*
  Plugin Name: Test Plugin
  Plugin URI:
  Description: ?
  Author: mirror12k
  Version: 0.0.1
  Author URI: http://www.www.www/
*/

if (!defined('ABSPATH')) die('indirect access');


require_once 'BasePlugin.php';



class TestPlugin extends BasePlugin
{
	public $plugin_name = 'test-plugin';

	public function load_hooks()
	{
		add_filter('post_type_link', array($this, 'rewrite_test_post_url'), 10, 3);
		add_action('pre_get_posts', array($this, 'add_test_post_to_pages'));
		add_filter('template_include', array($this, 'template_include_controller'));
	}

	public function wordpress_init()
	{
		error_log('Test Plugin wordpress_init');

		$this->register_test_post_type();
	}

	public function register_test_post_type()
	{
		register_post_type('test_plugin_post', array(
			'labels' => array(
				'name' => __( 'Test Posts', 'test_plugin_post' ),
				'singular_name' => __( 'Test Post', 'test_plugin_post' ),
				'add_new' => __( 'Add New', 'test_plugin_post' ),
				'add_new_item' => __( 'Add New Test Post', 'test_plugin_post' ),
				'edit_item' => __( 'Edit Test Posts', 'test_plugin_post' ),
				'new_item' => __( 'New Test Post', 'test_plugin_post' ),
				'view_item' => __( 'View Test Post', 'test_plugin_post' ),
				'search_items' => __( 'Search Test Posts', 'test_plugin_post' ),
				'not_found' => __( 'No Test Posts found', 'test_plugin_post' ),
				'not_found_in_trash' => __( 'No Test Posts found in Trash', 'test_plugin_post' ),
				'parent_item_colon' => __( 'Parent Test Post:', 'test_plugin_post'),
				'menu_name' => __( 'Test Posts', 'test_plugin_post' ),
			),
			'hierarchical' => true,
			'description' => __( 'Test Posts', 'test_plugin_post' ),
			'supports' => array( 'title', 'editor', 'comments', 'page-attributes' ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			// 'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array('slug' => false, 'with_front' => false),
			'capability_type' => 'post'
		));
	}

	public function template_include_controller($template)
	{
		global $post;
		error_log("debug template_include_controller: " . $post->post_type);
		if ($post->post_type === 'test_plugin_post')
			return $this->plugin_dir() . '/twig-template.php';
		else
			return $template;
	}

	// taken and modified from https://wordpress.stackexchange.com/questions/203951/remove-slug-from-custom-post-type-post-urls
	public function rewrite_test_post_url($post_link, $post, $leavename)
	{
		if ('test_plugin_post' != $post->post_type || 'publish' != $post->post_status)
			return $post_link;

		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

		return $post_link;
	}

	// taken and modified from https://wordpress.stackexchange.com/questions/203951/remove-slug-from-custom-post-type-post-urls
	public function add_test_post_to_pages($query)
	{
		// || 2 != count($query->query) || !isset($query->query['page'])
		if (!$query->is_main_query())
			return;

		// if (!empty( $query->query['name']))
			$query->set('post_type', array('post', 'test_plugin_post', 'page'));
	}
}


$plugin = new TestPlugin();
$plugin->load_plugin();



