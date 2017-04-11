<?php if (!defined('ABSPATH')) die('indirect access'); ?>
<?php
get_header();

global $test_plugin;

$loader = new Twig_Loader_Filesystem($test_plugin->plugin_dir());
// $loader = new Twig_Loader_Array(array(
//     'index' => 'Hello {{ name }}!',
// ));
$twig = new Twig_Environment($loader);

$template_args = array( 'app' => $test_plugin );
if (isset($test_plugin->SyntheticPageManager->active_view_controller))
	$template_args = array_merge($template_args, $test_plugin->SyntheticPageManager->active_view_controller->template_args());

// foreach ($template_args as $key => $val)
// 	error_log("debug template args: $key => " . json_encode($val));

echo $twig->render($test_plugin->SyntheticPageManager->active_synthetic_page['template'], $template_args);


get_footer();
