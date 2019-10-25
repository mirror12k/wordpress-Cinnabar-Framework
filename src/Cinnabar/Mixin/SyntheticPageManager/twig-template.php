<?php
// security
if (!defined('ABSPATH')) die('indirect access');

// access the manager to get our page details
global $synthetic_manager;



// render the arguments to the template
$template_args = array( 'app' => $synthetic_manager->app );
if (isset($synthetic_manager->active_view_controller))
	$template_args = array_merge($template_args, $synthetic_manager->active_view_controller->template_args());



// render header unless disabled
if (!isset($synthetic_manager->active_synthetic_page['skip_header_footer']))
	get_header();



// render the template
echo $synthetic_manager->render_template($synthetic_manager->active_synthetic_page['template'], $template_args);



// render footer unless disabled
if (!isset($synthetic_manager->active_synthetic_page['skip_header_footer']))
	get_footer();
