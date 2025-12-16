<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Exception\Audit\RiskCheckException;
use Imee\Models\Xsst\XsstUserForbiddenCheckLog;
use Imee\Models\Xsst\XsstUserForbiddenCheck;
use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\ModifyContext;
use Phalcon\Di;
use Imee\Service\Helper;

class ModifyProcess
{
    private $context;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $status = XsstUserForbiddenCheck::STATUS_UNDO;
        $logModel = new XsstUserForbiddenCheckLog();
        $logModel->op_id = Helper::getSystemUid();
        $logModel->uid = $this->context->uid;

        $logModel->status = $status;
        $logModel->log_id = $this->context->id;
        $logModel->dateline = time();
        $logModel->save();

        $rec = XsstUserForbiddenCheck::findFirst([
            'conditions' => 'log_id=:log_id: and uid=:uid:',
            'bind' => [
                'log_id' => $this->context->id,
                'uid' => $this->context->uid,
            ],
        ]);

        if ($rec && $rec->id) {
            $rec->status = $status;
            $rec->reason = '';
            $rec->save();
        } else {
            $checkModel = new XsstUserForbiddenCheck;
            $checkModel->uid = $this->context->uid;
            $checkModel->status = $status;
            $checkModel->reason = '';
            $checkModel->log_id = $this->context->id;
            $checkModel->dateline = time();
            $checkModel->save();
        }
    }
}
