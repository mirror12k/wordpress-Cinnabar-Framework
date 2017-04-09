<?php
/*
  Plugin Name: Test Plugin
  Plugin URI:
  Description: ?
  Author: mirror12k
  Version: 0.0.2
  Author URI: http://www.www.www/
*/

if (!defined('ABSPATH')) die('indirect access');

require_once 'vendor/autoload.php';

require_once 'Cinnabar/BasePlugin.php';

require_once 'Cinnabar/mixins/SyntheticPageManager/SyntheticPageManager.php';
require_once 'Cinnabar/mixins/UpdateTriggerManager/UpdateTriggerManager.php';
require_once 'Cinnabar/mixins/EmailManager/EmailManager.php';
require_once 'Cinnabar/mixins/AjaxGatewayManager/AjaxGatewayManager.php';
require_once 'TestPlugin/TestPlugin.php';


global $test_plugin;
$test_plugin = new TestPlugin();
$test_plugin->load_plugin();



