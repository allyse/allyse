<?php

use Klein\Klein;
use Tracy\Debugger;

// App config constants
defined('APP_DIR') or define('APP_DIR', __DIR__ . '/..'); // app dir
defined('BASE_PATH') or define('BASE_PATH', str_replace('index.php', '', $_SERVER['SCRIPT_NAME'])); // base path
define('BOOTSTRAP_DIR', __DIR__); // this dir
defined('VENDOR_DIR') or define('VENDOR_DIR', APP_DIR . '/vendor'); // vendor (composer)
defined('LOG_DIR') or define('LOG_DIR', APP_DIR . '/log'); // log
defined('TEMP_DIR') or define('TEMP_DIR', APP_DIR . '/temp'); // temp dir => user for compile and cache
defined('COMPILE_DIR') or define('COMPILE_DIR', TEMP_DIR . '/@compile'); // compiled templates dir
defined('CACHE_DIR') or define('CACHE_DIR', TEMP_DIR . '/@cache'); // cache dir
defined('VIEW_DIR') or define('VIEW_DIR', APP_DIR . '/view'); // templates dir
defined('CACHE') or define('CACHE', 3600); // lifetime in seconds
defined('DB_DRIVER') or define('DB_DRIVER', 'mysqli'); // mysql driver
defined('DB_EXTENDED') or define('DB_EXTENDED', TRUE); // extended results in mysql response
defined('DEBUG') or define('DEBUG', FALSE); // debug
defined('ERROR_404_PAGE') or define('ERROR_404_PAGE', BOOTSTRAP_DIR . '/view/assets/Template/error404.php'); // 404 template
defined('ERROR_500_PAGE') or define('ERROR_500_PAGE', BOOTSTRAP_DIR . '/view/assets/Template/error500.php'); // 500 template
defined('ROUTERS') or define('ROUTERS', 'App'); // routers to use
defined('TRACY') or define('TRACY', TRUE); // routers to use

// Autoloader
require VENDOR_DIR . '/autoload.php';
require BOOTSTRAP_DIR . '/loader.php';

// Debugger
if (TRACY === TRUE) {
    Debugger::$errorTemplate = ERROR_500_PAGE;
    Debugger::$maxDepth = 10;
    Debugger::enable(NULL, LOG_DIR);
}

// Create klein.php
$klein = new Klein();

// Register routers
foreach (explode(',', ',' . ROUTERS) as $router) {
    $class = ucfirst(trim($router)) . 'Router';
    (new $class())->create($klein);
}

// Run!
$klein->dispatch();