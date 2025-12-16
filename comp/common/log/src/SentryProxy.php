<?php

namespace Imee\Comp\Common\Log;

use Phalcon\Di;
use Sentry;
use Sentry\SentrySdk;

/**
 * sentry
 */
class SentryProxy
{
    private $sdk;

    public function __construct()
    {
        $flag = $this->isSend();
        if (!$flag) {
            return;
        }
        $uuid = Di::getDefault()->getShared('uuid');
        Sentry\init([
            'dsn' => ENV == 'prod' ? SENTRY_DSN : SENTRY_DSN_TEST,
        ]);
        $this->sdk = SentrySdk::getCurrentHub();
        $this->sdk->configureScope(function (\Sentry\State\Scope $scope) use ($uuid): void {
            $scope->setTag('trace_id', $uuid);
            $scope->setTag('env', ENV);
        });
    }

    private function isSend()
    {
        $flag = false;

        if (ENV != 'prod' && defined('SENTRY_OPEN') && SENTRY_OPEN) {
            $flag = true;
        }
        return $flag;
    }

    public function __call($func, $arguments)
    {
        $flag = $this->isSend();
        if (!$flag) {
            return;
        }

        return call_user_func_array(array($this->sdk, $func), $arguments);
    }
}
