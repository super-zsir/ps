<?php

namespace Imee\Comp\Common\Fixed;

use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Di;

class OutputError
{
    public function __construct($e)
    {
        @ob_clean();
        header('Cache-Control: private, no-cache, max-age=0, must-revalidate');
        header("Content-Type: text/html; charset=utf-8");
        header("HTTP/1.1 500 Server Error");
        $this->log($e);
        if (defined('DEBUG') && DEBUG) {
            $this->printDebug($e);
        }
    }

    private function log($e)
    {
        $uuid = Di::getDefault()->getShared('uuid');
        if (defined('CACHE_DIR')) {
            $logger = new FileAdapter(CACHE_DIR . DS . 'log' . DS . 'error.log');
        } else {
            $logger = new FileAdapter(ROOT . DS . 'cache' . DS . 'log' . DS . 'error.log');
        }

        $formatter = new LineFormatter("[%type%][%date%][{$uuid}] - %message%");
        $logger->setFormatter($formatter);
        $logger->setLogLevel(Logger::ERROR);
        $errorMessage = $e->getMessage();
        $encode = @mb_detect_encoding($errorMessage, array('UTF-8', 'GBK', 'GB2312'));
        $msg = $e->getFile() . ' : ' . $e->getLine() . ' : ' . $_SERVER['REQUEST_URI'] . '，' . preg_replace("/\n\r\t/", "", mb_convert_encoding($errorMessage, 'utf-8', $encode));
        $msg = $msg . "\n" . $e->getTraceAsString();
        $logger->error($msg);
    }

    private function printDebug($e)
    {
        $trace = $e->getTrace();
        $errorMessage = $e->getMessage();
        $encode = @mb_detect_encoding($errorMessage, array('UTF-8', 'GBK', 'GB2312'));
        $errorMessage = @mb_convert_encoding($errorMessage, 'utf-8', $encode);
        echo '<!DOCTYPE html>
		<html>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<style>
				*{padding:0;margin:0;}
				html, body{background-color:#FFFFFF}
				p{margin:5px 0}
				li{padding:5px 0}
				.layout{
					width:980px;
					margin:15px auto;
				}
			</style>
		</head>
		<body><div class="layout">';
        echo "<div><p>file: " . $e->getFile() . "</p><p>line: " . $e->getLine() . '</p><p>desc: ' . $errorMessage . '</p></div>';
        echo '<ul>';
        foreach ($trace as $error) {
            if (!isset($error['file'])) continue;
            echo "<li>{$error['file']}，{$error['line']}， {$error['class']}，{$error['function']}</li>";
        }
        echo '</ul></div>
		</body>
		</html>';
    }
}