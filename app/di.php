<?php

use Imee\Comp\Common\Fixed\File;
use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisSimple;
use Imee\Comp\Common\Fixed\CacheXcache;
use Imee\Comp\Common\Fixed\RedisSession;
use Imee\Comp\Common\Fixed\ImeeConfig;
use Imee\Libs\Event\DbEventListener;
use Phalcon\Di;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\MetaData\Xcache as XcacheMetaData;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Collection\Manager as CollectionManager;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Http\Response\Cookies;
use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;

$di = new FactoryDefault();

$eventsManager = new EventsManager();

$di->set('router', function () {
    require __DIR__ . '/routes.php';
    return $router;
});

//Register Volt as a service
$di->set('voltService', function ($view, $di) {
    $volt = new Volt($view, $di);
    $volt->setOptions(array(
        "compiledPath"      => function ($templatePath) {
            return ROOT . DS . 'cache' . DS . 'tpl' . DS . str_replace("/", "_", $templatePath) . '.php';
        },
        "compiledExtension" => ".php",
        "compiledSeparator" => ".",
        "stat"              => true,
        "compileAlways"     => false,
        "prefix"            => '',
    ));
    $compiler = $volt->getCompiler();
    $compiler->addFilter('date', function ($resolvedArgs, $exprArgs) {
        return 'date(\'' . $exprArgs[1]['expr']['value'] . '\', ' . $exprArgs[0]['expr']['value'] . ')';
    });
    $compiler->addFilter('strtotime', function ($resolvedArgs, $exprArgs) {
        return 'strtotime(' . $resolvedArgs . ')';
    });
    $compiler->addFilter('substr', function ($resolvedArgs, $exprArgs) {
        return 'substr(' . $resolvedArgs . ')';
    });
    $compiler->addFilter('intval', function ($resolvedArgs, $exprArgs) {
        return 'intval(' . $resolvedArgs . ')';
    });
    $compiler->addFilter('mb_substr', function ($resolvedArgs, $exprArgs) {
        return 'mb_substr(' . $resolvedArgs . ')';
    });
    return $volt;
});

// Setup the view component
$di->set('view', function () {
    $view = new View();
    $view->setViewsDir('cache/views/');
    $view->registerEngines(array(
        ".html" => 'voltService'
    ));
    return $view;
});

// Setup a base URI so that all generated URIs include the "v6" folder
$di->set('url', function () {
    $url = new UrlProvider();
    $url->setBaseUri('/');
    return $url;
});

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

//缓存model结构和sql
$di->set('modelsMetadata', function () {
    return new XcacheMetaData(array(
        "lifetime" => (ENV == 'dev') ? 1 : MODEL_CACHE_LIFETIME,
        "prefix"   => "model-meta-cache-v12"
    ));
});

$uuid = create_uuid();
$di->set('uuid', function () use ($uuid) {
    return $uuid;
});

$di->set('logger', function () use ($uuid) {
    $logger = new File(CACHE_DIR . DS . 'log' . DS . 'admin_debug.log');
    $formatter = new LineFormatter("[%type%][%date%][{$uuid}] - %message%");
    $logger->setFormatter($formatter);
    $logger->setLogLevel(defined('DEBUG') && DEBUG ? Logger::INFO : Logger::WARNING);
    $logger->begin();
    return $logger;
});

$di->set('dblogger', function () use ($uuid) {
    $logger = new File(CACHE_DIR . DS . 'log' . DS . 'admin_db.log');
    $formatter = new LineFormatter("[%type%][%date%][{$uuid}] - %message%");
    $logger->setFormatter($formatter);
    $logger->setLogLevel(defined('DEBUG') && DEBUG ? Logger::INFO : Logger::WARNING);
    $logger->begin();
    return $logger;
});

$di->set('modelsManager', new ModelsManager());
$di->set('collectionManager', new CollectionManager());

$di->set('cookies', function () {
    $cookies = new Cookies();
    $cookies->useEncryption(false);
    return $cookies;
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

$di->set('dispatcher', function () use ($eventsManager) {
    $dispatcher = new Dispatcher();
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
});

$di->set('eventsManager', function () use ($eventsManager) {
    return $eventsManager;
});

return $di;
