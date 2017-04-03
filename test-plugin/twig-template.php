<?php if (!defined('ABSPATH')) die('indirect access'); ?>
<?php
get_header();


require_once 'vendor/autoload.php';

$loader = new Twig_Loader_Array(array(
    'index' => 'Hello {{ name }}!',
));
$twig = new Twig_Environment($loader);

echo $twig->render('index', array('name' => 'User'));


get_footer();
