<?php
set_include_path(dirname(__FILE__) . '/classes/');

define('DIR_DATA', dirname(__FILE__) . '/data/');

require_once 'Loader.class.php';

$loader = new classes_Loader();
$loader->run();