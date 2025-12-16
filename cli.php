<?php

use Imee\Comp\Common\Log\LoggerProxy;
use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Di;
use Imee\Service\Helper;


/**
 * Process the console arguments
 */
$arguments = array('action' => 'main');
$params = array();
if (isset($argv[1])) {
    $arguments['task'] = $argv[1];
}
if (count($argv) >= 2) {
    for ($i = 2, $len = count($argv); $i < $len; $i += 2) {
        if (substr($argv[$i], 0, 1) == '-') {
            $key = substr($argv[$i], 1);
        } else {
            continue;
        }
        $params[$key] = isset($argv[$i + 1]) ? $argv[$i + 1] : null;
    }
}
$arguments['params'] = $params;

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cli_base.php');

// define global constants for the current task and action
define('CURRENT_TASK', (isset($arguments['task']) ? $arguments['task'] : null));
define('CURRENT_ACTION', (isset($arguments['action']) ? $arguments['action'] : null));


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
