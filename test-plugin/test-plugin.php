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
	public $registered_synthetic_pages = array();

	public function load_hooks()
	{
		add_filter('post_type_link', array($this, 'rewrite_test_post_url'), 10, 3);
		add_action('pre_get_posts', array($this, 'add_test_post_to_pages'));
		add_filter('template_include', array($this, 'template_include_controller'));
	}

	// public function wordpress_activate()
	// {
	// 	error_log('Test Plugin wordpress_activate');

	// 	// $this->update_synthetic_pages();
	// }

	public function wordpress_init()
	{
		error_log('Test Plugin wordpress_init');

		$this->register_test_post_type();
		$this->register_synthetic_pages(array(
			'synth-1' => array(),
			'synth-2' => array(),
		));
	}

	public function wordpress_loaded()
	{
		error_log('Test Plugin wordpress_loaded');

		$this->update_synthetic_pages();
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
		if (!$query->is_main_query() || !isset($query->query['page']))
			return;
		// error_log("debug isset(page): " . isset());
		// if (!empty( $query->query['name']))
			$query->set('post_type', array('post', 'test_plugin_post', 'page'));
	}

	public function register_synthetic_pages($pages)
	{
		foreach ($pages as $location => $page)
		{
			if (isset($this->registered_synthetic_pages[$location]))
				throw new Exception("synthetic page '$location' is registered twice");
			$this->registered_synthetic_pages[$location] = $page;
		}
	}

	public function update_synthetic_pages()
	{
		error_log("updating synthetic pages");
		$existing_pages = $this->list_synthetic_pages();

		$existing_page_map = $this->map_existing_pages($existing_pages);
		$unnecessary_pages = array();
		foreach ($existing_page_map as $location => $page)
		{
			error_log("found page '$location'");
			if (!isset($this->registered_synthetic_pages[$location]))
			{
				// error_log("page $location doesnt belong");
				$unnecessary_pages[] = $page;
			}
		}

		$missing_pages = array();
		foreach ($this->registered_synthetic_pages as $location => $page)
		{
			if (!isset($existing_page_map[$location]))
			{
				error_log("missing registered page $location");
				$missing_pages[] = $location;
			}
		}

		if (!empty($unnecessary_pages))
			$this->delete_synthetic_pages($unnecessary_pages);
		if (!empty($missing_pages))
			$this->create_synthetic_pages($missing_pages);
	}

	public function delete_synthetic_pages($pages)
	{
		foreach ($pages as $page)
		{
			error_log("deleting synthetic page $page->post_name");
			wp_delete_post($page->ID, false);
		}
	}

	public function create_synthetic_pages($locations)
	{
		foreach ($locations as $location)
		{
			error_log("creating synthetic page $location");
			wp_insert_post(array(
				'post_type' => 'test_plugin_post',
				'post_title' => $location,
				'post_name' => $location,
				'comment_status' => 'closed',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			));
		}
	}

	public function map_existing_pages($existing_pages)
	{
		$existing_page_map = array();
		foreach ($existing_pages as $page)
		{
			// get the page location from the slug
			$location = $page->post_name;

			// if the page has a parent chain, we need to look up the chain for the full location path
			if ($page->post_parent !== 0)
			{
				// error_log("page $location has a parent, looking for it");
				$current_page = $page;
				while ($current_page->post_parent !== 0)
				{
					$parent_page = null;
					foreach ($existing_pages as $page_iter)
						if ($page_iter->ID === $current_page->post_parent)
							$parent_page = $page_iter;
					if ($parent_page === null)
					{
						error_log("missing parent for page $current_page->post_name");
						break;
					}
					else
					{
						// error_log("found $location parent, $parent_page->post_name");
						$current_page = $parent_page;
						$location = $parent_page->post_name . '/' . $location;
					}
				}
			}

			// map the page
			$existing_page_map[$location] = $page;
		}

		return $existing_page_map;
	}

	public function list_synthetic_pages()
	{
		$query = new WP_Query(array(
			'post_type' => 'test_plugin_post',
			'posts_per_page' => -1,
		));

		return $query->posts;
	}
}


$plugin = new TestPlugin();
$plugin->load_plugin();



