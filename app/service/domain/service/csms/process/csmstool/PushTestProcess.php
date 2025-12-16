<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;

class PushTestProcess
{



    public function __construct($context)
    {
        $this->context = $context;
    }

    public function handle()
    {

        $data = $this->context->toArray();
        $data['content'] = json_decode($data['content'], true);
        $data['extra'] = $data['extra'] ? json_decode($data['extra'], true) : [];

        $pushData = [
            'cmd' => 'csms.push',
            'data' => $data
        ];

        $dataCleanService = new \Imee\Service\Domain\Service\Csms\Task\DataCleaningService();
        $cleanData = $dataCleanService->handle($pushData, \Imee\Helper\Constant\CsmsConstant::CSMS_NSQ);
        // 送风控系统
        if($cleanData){
            $service = new \Imee\Service\Domain\Service\Audit\SaasService();
            $return = $service->initData($cleanData);
        }else{
            $return = true;
        }
        // 因为消费 false代表成功，所以需要转义
        return $return ? CsmsWorkbenchException::throwException(CsmsWorkbenchException::CSMS_PUSH_TEST_ERROR) : true;
    }


}