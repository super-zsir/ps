<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('Asia/Shanghai');
set_time_limit(18);
define('ROOT', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
define('MAGIC', get_magic_quotes_gpc() ? true : false);
define('SESSION_AUTO_START', ini_get('session.auto_start') != '1' ? true : false);
define('APP_PATH', ROOT . DS . 'app');
define('IS_CMS', true);
define('RUNNING', '');
define('IS_CLI', substr(php_sapi_name(), 0, 3) == 'cli');

require_once(ROOT . DS . 'env.php');
if (!in_array(ENV, array('dev', 'alpha', 'prod'))) {
    exit('ENV Error');
}

if (ENV == 'dev') {
    define('CONFIG', dirname(dirname(ROOT)) . DS . 'ee-config-admin-dev');
} else {
    define('CONFIG', dirname(ROOT) . DS . 'ee-config-admin');
}

require_once(ROOT . DS . 'comp/common' . DS . 'fixed' . DS . 'Functions.php');
require_once(ROOT . DS . 'comp/common' . DS . 'fixed' . DS . 'Loader.php');
require_once(APP_PATH . DS . 'config_define.php');
require_once(APP_PATH . DS . 'ImeeApplication.php');
require_once(ROOT . DS . 'comp' . DS . 'autoload.php');
require_once(ROOT . DS . 'vendor' . DS . 'autoload.php');

ImeeApplication::instance()->run();
