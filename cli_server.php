<?php

use Phalcon\Di;
use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Imee\Service\Helper;

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cli_base.php');

$params = [];
$arguments = array('task' => 'chat', 'action' => 'main');
$arguments['params'] = array();
define('CURRENT_TASK', $arguments['task']);
define('CURRENT_ACTION', $arguments['action']);

try {
    Helper::debugger()->warning(json_encode($params));
    // handle incoming arguments
    $console->handle($arguments);
} catch (\Exception $e) {
    $uuid = Di::getDefault()->getShared('uuid');

    echo "[" . $uuid . "]" . $e->getMessage();
    $logger = new FileAdapter(CACHE_DIR . DS . 'log' . DS . 'error.log');
    $formatter = new LineFormatter("[%type%][%date%][{$uuid}][Cli] - %message%");
    $logger->setFormatter($formatter);
    $logger->setLogLevel(Logger::ERROR);
    $errorMessage = $e->getMessage();
    $encode = @mb_detect_encoding($errorMessage, array('UTF-8', 'GBK', 'GB2312'));
    $msg = $e->getFile() . ' : ' .  $e->getLine() . ' : ' . preg_replace("/\n\r\t/", "", mb_convert_encoding($errorMessage, 'utf-8', $encode));
    $msg = $msg . "\n" . $e->getTraceAsString();
    $logger->error("[{$arguments['task']}/{$arguments['action']}]".json_encode($params)." " . $msg);
    exit(255);
}
