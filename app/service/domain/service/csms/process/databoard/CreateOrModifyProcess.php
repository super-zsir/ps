<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Exception\Audit\ScheduleException;
use Imee\Models\Bms\BmsAuditTurnout;
use Imee\Models\Bms\BmsAuditTurnoutLog;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleOpContext;
use Imee\Service\Helper;
use Phalcon\Di;

/**
 *
 */
class CreateOrModifyProcess
{
    private $context;
    private $model;

    public function __construct(ScheduleOpContext $context)
    {
        $this->context = $context;
    }

    private function verify()
    {
        if (empty($this->context->id)) {
            $model = BmsAuditTurnout::findFirst([
                'conditions' => 'admin = :admin: and dateline=:dateline:',
                'bind' => [
                    'admin' => $this->context->admin,
                    'dateline' => strtotime(date('Y-m-01 00:00:00', strtotime($this->context->dateline))),
                ],
            ]);
            if ($model) {
                list($code, $msg) = ScheduleException::USER_EXIST;
                throw new ScheduleException($msg, $code);
            }
        } else {
            $this->model = BmsAuditTurnout::findFirst([
                'conditions' => 'id = :id:',
                'bind' => [
                    'id' => $this->context->id,
                ],
            ]);
            if (empty($this->model)) {
                //抛错
                list($code, $msg) = ScheduleException::DATA_NOT_FOUND;
                throw new ScheduleException($msg, $code);
            }

            if ($this->model->admin != $this->context->admin || $this->model->dateline != strtotime(date('Y-m-01 00:00:00', strtotime($this->context->dateline)))) {
                $model = BmsAuditTurnout::findFirst([
                    'conditions' => 'admin = :admin: and dateline=:dateline:',
                    'bind' => [
                        'admin' => $this->context->admin,
                        'dateline' => strtotime(date('Y-m-01 00:00:00', strtotime($this->context->dateline))),
                    ],
                ]);
                if ($model) {
                    list($code, $msg) = ScheduleException::USER_EXIST;
                    throw new ScheduleException($msg, $code);
                }
            }
        }
    }

    public function handle()
    {
        $this->verify();

        if (empty($this->context->id)) {
            $this->model = new BmsAuditTurnout;
        }

        $this->model->update_time = time();

        $this->model->admin = $this->context->admin;
        $this->model->turnout_num = $this->context->turnoutNum;

        $this->model->a_num = $this->context->aNum;
        $this->model->b_num = $this->context->bNum;
        $this->model->c_num = $this->context->cNum;

        $this->model->dateline = strtotime(date('Y-m-01 00:00:00', strtotime($this->context->dateline)));

        $this->model->setLogAttr([
            'action' => empty($this->context->id) ?
                BmsAuditTurnoutLog::ACTION_CREATE : BmsAuditTurnoutLog::ACTION_MODIFY,
            'op_uid' => $this->context->opUid,
        ]);
        $this->model->save();
    }
}
