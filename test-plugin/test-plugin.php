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
		// add_filter('do_parse_request', array($this, 'do_parse_request_hook'), 10, 3);
		// add_filter('query_vars', array($this, 'query_vars_hook'), 10, 1);
		// add_action('parse_request', array($this, 'parse_request_hook'));
		// add_action('parse_query', array($this, 'parse_query_hook'));
		// add_action('pre_get_posts', array($this, 'add_test_post_to_pages'));
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
			'synth-2/test-child' => array(),
		));
	}

	public function wordpress_loaded()
	{
		error_log('Test Plugin wordpress_loaded');

		$this->fill_out_parent_pages();
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
		// error_log("debug template_include_controller: " . $post->post_type);
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

	// public function do_parse_request_hook($b, $wp, $extra_query_vars)
	// {
	// 	global $wp_rewrite;
	// 	$s = json_encode($wp_rewrite->wp_rewrite_rules());
	// 	while (strlen($s) > 500)
	// 	{
	// 		error_log("debug wp_rewrite: " . substr($s, 0, 500));
	// 		$s = substr($s, 500);
	// 	}
	// 	error_log("debug wp_rewrite: " . $s);

	// 	error_log("debug do_parse_request_hook: " . json_encode($extra_query_vars));
	// 	// $query->query_vars['post_type'] = array('post', 'test_plugin_post', 'page');
	// 	return $b;
	// }

	// public function query_vars_hook($query)
	// {
	// 	error_log("debug query_vars_hook: " . json_encode($query));
	// 	// $query->query_vars['post_type'] = array('post', 'test_plugin_post', 'page');
	// 	return $query;
	// }

	// public function parse_request_hook($query)
	// {
	// 	error_log("debug parse_request_hook: " . json_encode($query->query_vars));
	// 	// $query->query_vars['post_type'] = array('post', 'test_plugin_post', 'page');
	// }

	// public function parse_query_hook($query)
	// {
	// 	error_log("debug parse_query_hook: " . json_encode($query->query));
	// }

	// taken and modified from https://wordpress.stackexchange.com/questions/203951/remove-slug-from-custom-post-type-post-urls
	// public function add_test_post_to_pages($query)
	// {
	// 	// || 2 != count($query->query) || !isset($query->query['page'])
	// 	error_log("debug add_test_post_to_pages: " . json_encode($query->query));
	// 	if (!$query->is_main_query() || !isset($query->query['page']))
	// 	// 	return;
	// 	// error_log("debug add_test_post_to_pages: " . $query->query['page']);
	// 	// // if (!empty( $query->query['name']))
	// 		$query->set('post_type', array('post', 'test_plugin_post', 'page'));
	// }

	public function register_synthetic_pages($pages)
	{
		foreach ($pages as $location => $page)
		{
			if (isset($this->registered_synthetic_pages[$location]))
				throw new Exception("synthetic page '$location' is registered twice");
			$this->registered_synthetic_pages[$location] = $page;
		}
	}

	public function break_down_location_chain($location)
	{
		$chain = array($location);
		$res = preg_match("#^(.+)/([^/]+)$#", $location, $matches);
		while ($res === 1)
		{
			$location = $matches[1];
			$chain[] = $location;

			$res = preg_match("#^(.+)/([^/]+)$#", $location, $matches);
		}

		return $chain;
	}

	public function fill_out_parent_pages()
	{
		$missing_parents = array();
		foreach ($this->registered_synthetic_pages as $location => $page)
		{
			$location_chain = $this->break_down_location_chain($location);
			foreach ($location_chain as $sub_location)
				if (!isset($this->registered_synthetic_pages[$sub_location]))
					$missing_parents[$sub_location] = true;
		}

		foreach ($missing_parents as $location => $v)
		{
			error_log("auto-filling missing parent $location");
			$this->registered_synthetic_pages[$location] = array();
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

	public static function get_synthetic_page_by_location($location)
	{
		$query = new WP_Query(array('post_type' => 'test_plugin_post', 'pagename' => $location));
		if ($query->have_posts())
			return $query->posts[0];
		else
			return false;
	}

	public function create_synthetic_pages($locations)
	{
		error_log("got locations to create: " . json_encode($locations));
		// sort the locations first to ensure that parents are created first
		sort($locations);
		foreach ($locations as $location)
		{
			$res = preg_match("#^(.+)/([^/]+)$#", $location, $matches);
			if ($res === 1)
			{
				$page_name = $matches[2];
				$parent_page = $this->get_synthetic_page_by_location($matches[1]);
				if ($parent_page === false)
					throw new Exception("missing parent '$matches[1]' for location '$location'");
				$parent_id = $parent_page->ID;
			}
			else
			{
				$page_name = $location;
				$parent_id = 0;
			}

			error_log("creating synthetic page $location");
			wp_insert_post(array(
				'post_type' => 'test_plugin_post',
				'post_title' => $page_name,
				'post_name' => $page_name,
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



