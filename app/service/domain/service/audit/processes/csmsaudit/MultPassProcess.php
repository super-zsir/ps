<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Csmsaudit;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Common\Redis\RedisBase;
use Imee\Exception\Audit\AuditWorkbenchException;
use Imee\Helper\Constant\AuditConstant;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineMultpassContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Phalcon\Di;

class MultPassProcess
{

	use CsmsTrait;

    protected $context;
    protected $power;

    public function __construct(TextMachineMultpassContext $context)
    {
        $this->context = $context;
        // 如果是主管 走循环清理，如果是审核人员，清理自己的
        $this->power = $this->isLeaderPurview() ? 'old' : 'new';
    }


    public function handle()
    {
        return $this->muntpass();
    }

    /**
     * 新审核系统-审核操作
     *
     * 需要将批量操作改成 旧系统那种
     */
    public function muntpass()
    {
        $module = isset($this->context->module) ? $this->context->module : '';
        $choice = isset($this->context->choice) ? $this->context->choice : '';
        $deleted = $this->context->deleted;
        $ids = $this->context->ids;
        $admin = $this->context->admin;
        $redis_audit = Di::getDefault()->getShared('redis');

        $redis = new RedisBase(RedisBase::REDIS_ADMIN);

        $all_ids = is_array($ids) ? $ids : explode(',', $ids);

        if ($this->power == 'new') {
            $redis_key = AuditConstant::REDIS_STAFF_TASK_PRE . $module . '-' . $choice;
            $check_task = $this->checkTaskTimeout($module, $choice, $admin, $all_ids);
            if (!$check_task) {
                AuditWorkbenchException::throwException(AuditWorkbenchException::TASK_TIME_OUT);
            }
        }

        foreach ($all_ids as $k => $v) {
            $cache_list = [];
            $rec = CsmsAudit::findFirst($v);
            // 只能审核一次
            if (!$rec || $rec->deleted != CsmsConstant::CSMS_STATE_UNCHECK) {
                continue;
//                AuditWorkbenchException::throwException(AuditWorkbenchException::TASK_VERIFY_ERROR);
            }


            // 判断当前一条是否有下一阶段
            $nextStage = $this->hasNextStage($choice, CsmsConstant::CSMS_RECHECK);
            if ($nextStage) {
                // 按照百分比进
                $choiceStage = CsmsChoiceStage::findFirst([
                    'conditions' => 'choice = :choice: and stage = :stage:',
                    'bind' => [
                        'choice' => $choice,
                        'stage' => CsmsConstant::CSMS_RECHECK
                    ]
                ]);
                if($choiceStage){
                    $percent = $choiceStage->inspect;
                    if($percent){
                        $rand_number = mt_rand(1, 100);
                        if($rand_number <= $percent){
                            // 更新初审数量缓存
                            if ($redis_audit->hExists('CsmsAudit:Deleted2', $rec->choice)) {
                                $redis_audit->hIncrBy('CsmsAudit:Deleted2', $rec->choice, 1);
                            }
                            $rec->deleted2 = CsmsConstant::CSMS_STATE_UNCHECK;
                        }
                    }
                }
            }

            $rec->deleted = $deleted;
            $rec->op_dateline = time();
            $rec->op = $admin;
            $rec->save();


            NsqClient::publish(NsqConstant::TOPIC_CSMS_REVIEW, array(
                'cmd' => 'csms.verify',
                'data' => $rec->toArray(),
            ));
            $cache_list[] = $rec->toArray();

            // 管理员审核
            if ($this->power == 'old') {
                $this->managerDeleteRedisTask(CsmsConstant::CSMS_AUDIT, $rec->choice, $rec->id);
            }

            // 普通审核人员审核
            if ($this->power == 'new') {
//                foreach (explode(',', $ids) as $key => $value) {
//                    $redis->sRem($redis_key . '-' . $admin, $value);
//                }
                $redis->sRem($redis_key . '-' . $admin, $v);
                // 增加当天已审核数
                if ($cache_list) {
                    $cache_data = [];
                    foreach ($cache_list as $cache_key => $cache_value) {
                        $cache_data[$cache_value['id']] = $cache_value['choice'];
                    }
                    $this->changeCacheCount($module, $cache_data);
                }
            }
        }

        return true;
    }


}