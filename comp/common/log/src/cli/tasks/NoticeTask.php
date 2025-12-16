<?php

use Imee\Comp\Common\Log\Service\ErrorLogService;
use Imee\Comp\Common\Log\Service\NoticeService;

/**
 * 通知脚本
 */
class NoticeTask extends CliApp
{
    public function mainAction(array $params = [])
    {
        $process = $params['process'] ?? 0;
        $this->console('start!!!');
        switch ($process) {
            case 1:
                $this->sendNotice();
                break;
            case 2:
                $this->errorNotice();
                break;
            default:
                exit('process error');
        }
        $this->console('end!!!');
    }

    // 发送敏感通知
    public function sendNotice()
    {
        try {
            NoticeService::sendNotice();
        } catch (Exception $e) {
            $this->console('error: ' . $e->getMessage());
        }
    }

    // 发送异常通知
    public function errorNotice()
    {
        try {
            ErrorLogService::errorNotice();
        } catch (Exception $e) {
            $this->console('error: ' . $e->getMessage());
        }
    }

    protected function console($msg)
    {
        echo sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $msg);
    }
}