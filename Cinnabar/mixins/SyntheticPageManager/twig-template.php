<?php if (!defined('ABSPATH')) die('indirect access'); ?>
<?php
get_header();

global $synthetic_manager;



$template_args = array( 'app' => $synthetic_manager->app );
if (isset($synthetic_manager->active_view_controller))
	$template_args = array_merge($template_args, $synthetic_manager->active_view_controller->template_args());

// foreach ($template_args as $key => $val)
// 	error_log("debug template args: $key => " . json_encode($val));
echo $synthetic_manager->render_template($synthetic_manager->active_synthetic_page['template'], $template_args);



get_footer();
