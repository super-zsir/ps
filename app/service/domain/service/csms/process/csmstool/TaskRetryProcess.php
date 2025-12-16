<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xss\CsmsTaskLog;
use Imee\Service\Domain\Service\Csms\Exception\CsmsToolException;

class TaskRetryProcess
{

    public function __construct($context)
    {
        $this->context = $context;
    }


    public function handle()
    {
        $ids = $this->context->ids;
        if (!$ids) {
            CsmsToolException::throwException(CsmsToolException::TASK_RETRY_ERROR);
        }

        $tasks = CsmsTaskLog::find([
            'conditions' => "id in ({ids:array})",
            'bind' => [
                'ids' => $ids
            ]
        ])->toArray();
        if (!$tasks) {
            CsmsToolException::throwException(CsmsToolException::TASK_RETRY_NULL);
        }

        foreach ($tasks as $task) {
            $check_data = json_decode($task['check_data'], true);
            $data = $check_data['data'] ?: [];
            if (!$data) {
                CsmsToolException::throwException(CsmsToolException::TASK_RETRY_CHECKNULL);
            }

            $data['extra'] = [
                'retry' => 1
            ];

            $csmsData = [
                'cmd' => 'csms.push',
                'data' => $data
            ];

            if (ENV == 'dev') {
                // 数据清洗 - 守护进程的类都必须用单例模式
                $dataCleanService = new \Imee\Service\Domain\Service\Csms\Task\DataCleaningService();
                $cleanData = $dataCleanService->handle($csmsData, 'nsq');

                // 送风控系统
                if ($cleanData) {
                    $service = new \Imee\Service\Domain\Service\Csms\SaasService();
                    $return = $service->initData($cleanData);
                } else {
                    $return = false;
                }
                return $return;
            }

            $res = NsqClient::publish(NsqConstant::TOPIC_CSMS_NSQ, $csmsData);

            if (!$res) {
                CsmsToolException::throwException(CsmsToolException::TASK_RETRY_FAILED);
            }

        }
        return true;

    }

}