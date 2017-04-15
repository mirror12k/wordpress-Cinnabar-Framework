<?php if (!defined('ABSPATH')) die('indirect access'); ?>
<?php
get_header();

global $synthetic_manager;

$loader = new Twig_Loader_Filesystem($synthetic_manager->app->plugin_dir());
// $loader = new Twig_Loader_Array(array(
//     'index' => 'Hello {{ name }}!',
// ));
$twig = new Twig_Environment($loader);

$template_args = array( 'app' => $synthetic_manager->app );
if (isset($synthetic_manager->active_view_controller))
	$template_args = array_merge($template_args, $synthetic_manager->active_view_controller->template_args());

// foreach ($template_args as $key => $val)
// 	error_log("debug template args: $key => " . json_encode($val));

echo $twig->render($synthetic_manager->active_synthetic_page['template'], $template_args);


get_footer();
