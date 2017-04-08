<?php if (!defined('ABSPATH')) die('indirect access'); ?>
<?php
get_header();

global $test_plugin;
require_once 'vendor/autoload.php';

$loader = new Twig_Loader_Array(array(
    'index' => 'Hello {{ name }}!',
));
$twig = new Twig_Environment($loader);

$template_args = array();
if (isset($test_plugin->active_view_controller))
	$template_args = array_merge($template_args, $test_plugin->active_view_controller->template_args());


echo $twig->render('index', $template_args);


get_footer();
