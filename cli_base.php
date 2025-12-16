<?php

use Imee\Comp\Common\Fixed\File;
use Imee\Comp\Common\Phpnsq\NsqProxyClient;
use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Comp\Common\Fixed\CacheXcache;
use Imee\Comp\Common\Fixed\Loader;
use Imee\Comp\Common\Fixed\RedisSession;
use Imee\Comp\Common\Fixed\ImeeConfig;
use Imee\Libs\Event\DbEventListener;
use Phalcon\Mvc\Model\MetaData\Memory as MemoryMetaData;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Di;
use Phalcon\DI\FactoryDefault\CLI as CliDI;
use Phalcon\CLI\Console as ConsoleApp;
use Phalcon\Http\Response\Cookies;
use Phalcon\Http\Request;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Collection\Manager as CollectionManager;

set_time_limit(0);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('Asia/Shanghai');
define('ROOT', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
define('MAGIC', false);
define('IS_CLI', substr(php_sapi_name(), 0, 3) == 'cli');
define('SESSION_AUTO_START', ini_get('session.auto_start') != '1' ? true : false);
define('APP_PATH', ROOT . DS . 'app');
define('CLI_PATH', ROOT . DS . 'cli');
define('IS_CMS', false);
define('RUNNING', '');

if (!IS_CLI) {
    header("403 Forbidden");
    exit();
}

require_once(ROOT . DS . 'env.php');
if (!in_array(ENV, array('dev', 'alpha', 'prod'))) {
    die("ENV Error");
}

if (ENV == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
}

if (ENV == 'dev') {
    define('CONFIG', dirname(dirname(ROOT)) . DS . 'ee-config-admin-dev');
} else {
    define('CONFIG', dirname(ROOT) . DS . 'ee-config-admin');
}

require_once(ROOT . DS . 'comp/common' . DS . 'fixed' . DS . 'Functions.php');
require_once(ROOT . DS . 'comp/common' . DS . 'fixed' . DS . 'Loader.php');
require_once(APP_PATH . DS . 'config_define.php');
require_once(ROOT . DS . 'comp' . DS . 'autoload.php');
require_once(ROOT . DS . 'vendor' . DS . 'autoload.php');

$di = new CliDI();

/**
 * Register the autoloader and tell it to register the tasks directory
 */
$loader = new Loader();
$loader->registerNamespaces(array(
    "Imee\Libs"     => ROOT . DS . "app/libs/",
    "Imee\Service"  => ROOT . DS . "app/service/",
    "Imee\Exception"=> ROOT . DS . "app/exception/",
    "Imee\Cli\Libs" => ROOT . DS . "cli/libs/",
    "Config"        => CONFIG . "/",
    "Imee\Helper"   => ROOT . DS . "app/helper/",
    "Imee\Export"   => ROOT . DS . "app/export/",
    "Imee\Models"   => ROOT . DS . "app/models/",
    "Imee\Comp"     => ROOT . DS . "comp/",
    "OSS"           => ROOT . DS . "comp/common/oss/",
));

//自动加载组件化命名空间
require_once ROOT . '/comp/loader.php';


// 加载comp 的task
if(function_exists('task_dir_load')){
    task_dir_load($params, $loader);
}else{
    $loader->registerDirs(array(
        ROOT . DS . 'cli/tasks/',//tasks目录需要引入，无法使用注册命名空间引入
    ));
}

$loader->register();

$console = new ConsoleApp();
$console->setDI($di);

$di->setShared('console', $console);

$di->set('modelsMetadata', function () {
    return new MemoryMetaData();
});

$di->set('collectionManager', new CollectionManager());

// Setup Global Config
$di->set('config', function () {
    return new ImeeConfig();
});

$databaseConfigs = Di::getDefault()->getShared('config')->database;

// Setup the database service
foreach ($databaseConfigs as $dbname => $config) {
    $di->set($dbname, function () use ($config) {
        $db = new DbAdapter(array(
            "host"     => $config['host'],
            "port"     => $config['port'],
            "username" => $config['username'],
            "password" => $config['password'],
            "dbname"   => $config['dbname'],
            "charset"  => $config['charset'],
            "options"  => array(
                \PDO::ATTR_TIMEOUT            => 2, //链接超时
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $config['charset'],
                \PDO::ATTR_PERSISTENT         => false, //我们使用短链接
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                // \PDO::ATTR_STRINGIFY_FETCHES => true,
            )
        ));
        $eventsManager = new EventsManager();
        $eventsManager->attach('db', new DbEventListener());
        $db->setEventsManager($eventsManager);
        return $db;
    });
}
$uuid = create_uuid();
$di->set('uuid', function () use ($uuid) {
    return $uuid;
});

$di->set('logger', function () use ($uuid) {
    $logger = new File(CACHE_DIR. DS . 'log' . DS . "cli_debug.log");
    $formatter = new LineFormatter("[%type%][%date%][{$uuid}][Cli] - [".CURRENT_TASK."]%message%");
    $logger->setFormatter($formatter);
    $logger->setLogLevel(Logger::WARNING);
    //$logger->begin(); 关闭日志
    return $logger;
});

$di->set('dblogger', function () use ($uuid) {
    $logger = new File(CACHE_DIR. DS . 'log' . DS . 'cli_db.log');
    $formatter = new LineFormatter("[%type%][%date%][{$uuid}][Cli] - [".CURRENT_TASK."]%message%");
    $logger->setFormatter($formatter);
    $logger->setLogLevel(defined('DEBUG') && DEBUG ? Logger::INFO : Logger::WARNING);
    //$logger->begin(); 关闭日志
    return $logger;
});

$di->set('cookies', function () {
    $cookies = new Cookies();
    $cookies->useEncryption(false);
    return $cookies;
});
$di->set('request', function () {
    return new Request();
});
$di->set('xcache', function () {
    return new CacheXcache();
});
$di->set('redis', function () {
    return new RedisSimple(RedisBase::REDIS_CACHE);
});

$di->set('session', function () {
    $session = new RedisSession(array(
        'uniqueId' => SESSION_UNIQUE,
        'lifetime' => SESSION_LIFETIME,
        'prefix'   => SESSION_PRIFIX
    ));
    $session->setName(SESSION_NAME);
    return $session;
});
$di->set('nsq_proxy', function () {
    return new NsqProxyClient();
});
