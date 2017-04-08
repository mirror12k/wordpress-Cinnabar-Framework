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

require_once 'Cinnabar/BasePlugin.php';

require_once 'mixins/SyntheticPageManager/SyntheticPageManager.php';
require_once 'TestPlugin/TestPlugin.php';
require_once 'vendor/autoload.php';


global $test_plugin;
$test_plugin = new TestPlugin();
$test_plugin->load_plugin();



