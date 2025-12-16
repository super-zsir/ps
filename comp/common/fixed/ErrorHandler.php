<?php

namespace Imee\Comp\Common\Fixed;

use Phalcon\Di;
use Phalcon\DiInterface;

class ErrorHandler
{
    const DEFAULT_ERROR_CODE = 101;
    const DEFAULT_EXCEPTION_CODE = 101;
    const ENV_PRODUCTION = 'prod';
    const ENV_DEVELOPMENT = 'dev';
    const ENV_TEST = 'test';
    const ENV_ALPHA = 'alpha';

    /**
     * Registers itself as error and exception handler
     * @return void
     */
    public static function register()
    {
        switch (ENV) {
            case self::ENV_PRODUCTION:
            default:
                ini_set('display_errors', 0);
                error_reporting(0);
                break;
            case self::ENV_TEST:
            case self::ENV_DEVELOPMENT:
            case self::ENV_ALPHA:
                ini_set('display_errors', 1);
                error_reporting(-1);
                break;
        }

        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                if (!($errno & error_reporting())) {
                    return;
                }
                $options = [
                    'success' => false,
                    'code'    => self::DEFAULT_ERROR_CODE,
                    'msg'     => $errstr,
                    'data'    => [
                        'file' => $errfile,
                        'line' => $errline,
                    ]
                ];
                static::handle(
                    'error_handler',
                    $options
                );
            }
        );

        set_exception_handler(
            function ($e) {
                $options = [
                    'success' => false,
                    'code'    => self::DEFAULT_EXCEPTION_CODE,
                    'msg'     => $e->getMessage(),
                    'data'    => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                ];
                static::handle(
                    'exception_handler',
                    $options
                );
            }
        );

        register_shutdown_function(
            function () {
                if (!is_null($options = error_get_last())) {
                    static::handle(
                        'error_get_last',
                        $options
                    );
                }
            }
        );
    }

    /**
     * @param $type
     * @param $options
     * @return void
     */
    public static function handle($type, $options)
    {
        $di = Di::getDefault();
        $message = json_encode($options);
        if (!$di instanceof DiInterface) {
            echo $message;
        }
        $logger = $di->getShared('logger');
        $logger->error("[{$type}]{$message}");
        if (IS_CLI) {
            echo $message;
        } else {
            $response = $di->getShared('response');
            $response->setJsonContent($options);
            $response->send();
        }
        exit;
    }
}
