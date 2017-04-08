<?php



class SyntheticPageManager extends BasePluginMixin
{
	public $registered_synthetic_pages = array();

	public $active_synthetic_page;
	public $active_view_controller;

	public function register()
	{
		$this->register_synthetic_page_post_type();
	}

	public function load_hooks()
	{
		add_filter('post_type_link', array($this, 'rewrite_test_post_url'), 10, 3);
		add_action('template_redirect', array($this, 'template_redirect_controller'));
		add_filter('template_include', array($this, 'template_include_controller'));
		add_filter('rewrite_rules_array', array($this, 'wordpress_rewrite_rules_array'));
		add_action('add_meta_boxes_synthetic_page', array($this, 'add_meta_boxes_synthetic_page'));
		add_action('wp_enqueue_scripts', array($this, 'wordpress_enqueue_scripts'));
		// add_filter('wp_title', array($this, 'template_title_controller'), 10, 3);
		add_filter('document_title_parts', array($this, 'template_title_controller'));
	}

	public function wordpress_loaded()
	{
		error_log('Test Plugin wordpress_loaded');

		$this->fill_out_parent_pages();
		$this->update_synthetic_pages();
		$this->update_rewrite_rules();
	}

	public function register_synthetic_page_post_type()
	{
		register_post_type('synthetic_page', array(
			'labels' => array(
				'name' => __( 'Synthetic Pages', 'synthetic_page' ),
				'singular_name' => __( 'Synthetic Page', 'synthetic_page' ),
				'add_new' => __( 'Add New', 'synthetic_page' ),
				'add_new_item' => __( 'Add New Synthetic Page', 'synthetic_page' ),
				'edit_item' => __( 'Edit Synthetic Pages', 'synthetic_page' ),
				'new_item' => __( 'New Synthetic Page', 'synthetic_page' ),
				'view_item' => __( 'View Synthetic Page', 'synthetic_page' ),
				'search_items' => __( 'Search Synthetic Pages', 'synthetic_page' ),
				'not_found' => __( 'No Synthetic Pages found', 'synthetic_page' ),
				'not_found_in_trash' => __( 'No Synthetic Pages found in Trash', 'synthetic_page' ),
				'parent_item_colon' => __( 'Parent Synthetic Page:', 'synthetic_page'),
				'menu_name' => __( 'Synthetic Pages', 'synthetic_page' ),
			),
			'hierarchical' => true,
			'description' => __( 'Synthetic Pages', 'synthetic_page' ),
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
		));
	}

	public function template_title_controller($title)
	{
		// error_log('debug template_title_controller: ' . json_encode($title));
		if (isset($this->active_synthetic_page))
		{
			if (isset($this->active_synthetic_page['title']))
				$title['title'] = $this->active_synthetic_page['title'];
			elseif (isset($this->active_view_controller))
				$title['title'] = $this->active_view_controller->template_title();
		}

		// $this->update_synthetic_pages();
		return $title;
	}

	public function wordpress_enqueue_scripts()
	{
		if (isset($this->active_synthetic_page) && isset($this->active_synthetic_page['scripts']))
		{
			foreach ($this->active_synthetic_page['scripts'] as $script_name => $script)
				wp_enqueue_script('synthetic-page-js-include-' . $script_name, $this->plugin_url($script));
			if (isset($this->active_view_controller))
			{
				foreach ($this->active_view_controller->js_args() as $script_name => $args)
					wp_localize_script('synthetic-page-js-include-' . $script_name, $args['variable'], $args['args']);
			}
		}
	}

	public function wordpress_rewrite_rules_array($rules)
	{
		$newrules = array();
		foreach ($this->registered_synthetic_pages as $location => $page)
			$newrules += $this->render_rewrite_rules($page);

		return $newrules + $rules;
	}

	public function update_rewrite_rules()
	{
		$rules = get_option('rewrite_rules');
		foreach ($this->registered_synthetic_pages as $location => $page)
			foreach ($this->render_rewrite_rules($page) as $src => $dst)
			{
				if (!isset($rules[$src]) || $rules[$src] !== $dst)
				{
					error_log("flushing rewrite rules because of missing rule $src for $dst");

					global $wp_rewrite;
					$wp_rewrite->flush_rules();

					return;
				}
			}
	}

	public function render_rewrite_rules($synthetic_page)
	{
		if (isset($synthetic_page['rewrite_rules']))
		{
			$rendered_rules = array();
			foreach ($synthetic_page['rewrite_rules'] as $rule => $rewrite)
				$rendered_rules[$this->render_rewrite_rule($synthetic_page, $rule)] = $this->render_rewrite_rule($synthetic_page, $rewrite);
			return $rendered_rules;
		}
		else
			return array();
	}

	public function render_rewrite_rule($synthetic_page, $rule)
	{
		$rule = str_replace('{{path}}', $synthetic_page['path'], $rule);
		if (isset($synthetic_page['virtual_paths']))
			foreach ($synthetic_page['virtual_paths'] as $name => $path)
				$rule = str_replace("{{{$name}}}", $path, $rule);
		return $rule;
	}


	public function add_meta_boxes_synthetic_page($post)
	{
		add_meta_box(
			'synthetic_page-config',
			__( 'Synthetic Page Config', 'synthetic_page'),
			array($this, 'render_synthetic_page_config_meta_box'),
			'synthetic_page',
			'normal',
			'default'
		);
	}

	public function render_synthetic_page_config_meta_box($page)
	{
		$page_location = $this->map_full_page_location($page);
		$page_config = $this->registered_synthetic_pages[$page_location];
		$rewrite_rules = $this->render_rewrite_rules($page_config);

		
?>
<table class="form-table">

	<tr>
		<th><label for="full_path" class="full_path_label"><?php echo htmlentities(__('Full Path', 'synthetic_page')); ?></label></th>
		<td>
			<?php echo htmlentities($page_location) ?>
		</td>
	</tr>
	<?php if (isset($page_config['view_controller'])) { ?>
	<tr>
		<th><label for="view_controller" class="view_controller_label"><?php echo htmlentities(__('View Controller', 'synthetic_page')); ?></label></th>
		<td>
			<?php echo htmlentities($page_config['view_controller']) ?>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<th><label for="rewrite_rules" class="rewrite_rules_label"><?php echo htmlentities(__('Rewrite Rules', 'synthetic_page')); ?></label></th>
	</tr>
	<?php foreach ($rewrite_rules as $src => $dst) { ?>
	<tr>
		<th><?php echo htmlentities($src); ?></th>
		<td><?php echo htmlentities($dst) ?></td>
	</tr>
	<?php } ?>

</table>
<?php
	}

	public function template_redirect_controller()
	{
		global $post;
		// error_log("debug template_redirect_controller: " . $post->post_type);
		if ($post->post_type === 'synthetic_page')
		{
			$location = $this->map_full_page_location($post);
			$this->active_synthetic_page = $this->registered_synthetic_pages[$location];
			if (isset($this->active_synthetic_page['view_controller']))
			{
				$this->active_view_controller = new $this->active_synthetic_page['view_controller']($this, $this->active_synthetic_page);

				$this->active_view_controller->template_redirect();
			}
		}
	}

	public function template_include_controller($template)
	{
		// error_log("debug template_include_controller: " . $post->post_type);
		if (isset($this->active_synthetic_page))
			return $this->app->plugin_dir() . '/twig-template.php';
		else
			return $template;
	}

	// taken and modified from https://wordpress.stackexchange.com/questions/203951/remove-slug-from-custom-post-type-post-urls
	public function rewrite_test_post_url($post_link, $post, $leavename)
	{
		if ('synthetic_page' != $post->post_type || 'publish' != $post->post_status)
			return $post_link;

		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

		return $post_link;
	}

	public function register_synthetic_pages($pages)
	{
		foreach ($pages as $location => $page)
		{
			if (isset($this->registered_synthetic_pages[$location]))
				throw new Exception("synthetic page '$location' is registered twice");

			$page['path'] = $location;
			if (!isset($page['rewrite_rules']))
				$page['rewrite_rules'] = array();
			if (!isset($page['rewrite_rules']['{{path}}/?$']))
				$page['rewrite_rules']['{{path}}/?$'] = 'index.php?synthetic_page={{path}}';

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
		// error_log("updating synthetic pages");
		$existing_pages = $this->list_synthetic_pages();

		$existing_page_map = $this->map_existing_pages($existing_pages);
		$unnecessary_pages = array();
		foreach ($existing_page_map as $location => $page)
		{
			// error_log("found page '$location'");
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
		$query = new WP_Query(array('post_type' => 'synthetic_page', 'pagename' => $location));
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
				'post_type' => 'synthetic_page',
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
			if ($page->post_parent)
			{
				// error_log("page $location has a parent, looking for it");
				$current_page = $page;
				while ($current_page->post_parent)
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

	public function map_full_page_location($page)
	{
		// get the page location from the slug
		$location = $page->post_name;

		// if the page has a parent chain, we need to look up the chain for the full location path
		if ($page->post_parent)
		{
			// error_log("page $location has a parent: $page->post_parent, looking for it");
			$current_page = $page;

			while ($current_page->post_parent)
			{
				$parent_page = $this->get_synthetic_page_by_id($current_page->post_parent);
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

		return $location;
	}

	public function get_synthetic_page_by_id($id)
	{
		$query = new WP_Query(array(
			'post_type' => 'synthetic_page',
			'p' => $id,
		));
		if ($query->have_posts())
			return $query->posts[0];
		else
			return null;
	}


	public function list_synthetic_pages()
	{
		$query = new WP_Query(array(
			'post_type' => 'synthetic_page',
			'posts_per_page' => -1,
		));

		return $query->posts;
	}
}


