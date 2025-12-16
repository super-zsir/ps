<?php

namespace Imee\Service\Domain\Service\Audit\Processes\Csmsrecheck;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Helper\Constant\AuditConstant;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineMultpassContext;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Phalcon\Di;

class MultPassProcess
{
	use CsmsTrait;

	protected $context;
	protected $power = 'old';

	public function __construct(TextMachineMultpassContext $context)
	{
		$this->context = $context;
		// 如果是主管 走循环清理，如果是审核人员，清理自己的
		$this->power = $this->isLeaderPurview() ? 'old' : 'new';
	}

	public function handle()
	{
		return $this->newMultpass();
	}

	private function newMultpass()
	{
		$module = isset($this->context->module) ? $this->context->module : '';
		$choice = isset($this->context->choice) ? $this->context->choice : '';
		$deleted = $this->context->deleted;
		$ids = $this->context->ids;
		$admin = $this->context->admin;
        $redis_audit = Di::getDefault()->getShared('redis');

		$now = time();
        $all_ids = is_array($ids) ? $ids : explode(',', $ids);


		if ($this->power == 'new') {
			$redis = new RedisBase(RedisBase::REDIS_ADMIN);
			$redis_key = AuditConstant::REDIS_STAFF_TASK_PRE . $module . '-' . $choice;
			$check_task = $this->checkTaskTimeout($module, $choice, $admin, $all_ids);
			if (!$check_task) {
				CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASK_TIME_OUT);
			}
		}


		$dirtys = CsmsAudit::find([
			'conditions' => 'id in ({ids:array})',
			'bind' => ['ids' => $all_ids]
		]);

		foreach ($dirtys as $dirty) {
            $cache_list = [];
            $cache_data = [];
            // 已经审核过
            if(in_array($dirty->deleted2, CsmsConstant::$allow_state)) continue;
            // 审核状态不对的
			if (!in_array($deleted, CsmsConstant::$allow_state)) continue;

            $dirty->deleted2 = $deleted;
            $dirty->op2 = $admin;
            $dirty->op_dateline2 = $now;

			// 判断当前一条是否有下一阶段
			$nextStage = $this->hasNextStage($choice, CsmsConstant::CSMS_INSPECT);

			if($nextStage){
			    // 初审复审不一致全进质检
                if($dirty->deleted != $dirty->deleted2){
                    // 更新质检数量缓存
                    if ($redis_audit->hExists('CsmsAudit:Deleted3', $dirty->choice)) {
                        $redis_audit->hIncrBy('CsmsAudit:Deleted3', $dirty->choice, 1);
                    }
                    $dirty->deleted3 = CsmsConstant::CSMS_STATE_UNCHECK;
                }
                // 初审复审一致按百分比比进质检
                if($dirty->deleted == $dirty->deleted2){
                    $choice = $dirty->choice;
                    $choiceStage = CsmsChoiceStage::findFirst([
                        'conditions' => 'choice = :choice: and stage = :stage:',
                        'bind' => [
                            'choice' => $choice,
                            'stage' => CsmsConstant::CSMS_INSPECT
                        ]
                    ]);
                    if($choiceStage){
                        $percent = $choiceStage->inspect;
                        if($percent){
                            $rand_number = mt_rand(1, 100);
                            if($rand_number <= $percent){
                                // 更新质检数量缓存
                                if ($redis_audit->hExists('CsmsAudit:Deleted3', $dirty->choice)) {
                                    $redis_audit->hIncrBy('CsmsAudit:Deleted3', $dirty->choice, 1);
                                }
                                $dirty->deleted3 = CsmsConstant::CSMS_STATE_UNCHECK;
                            }
                        }
                    }
                }
			}
			$dirty->save();
			$cache_list[] = $dirty->toArray();

			if ($this->power == 'old') {
				$this->oldDeleteRedisTask(CsmsConstant::CSMS_RECHECK, $dirty->choice, $dirty->id);
			}

			// 初审通过 复审不通过的往admin里发消息
			if ($dirty->deleted == CsmsConstant::CSMS_STATE_PASS && $deleted == CsmsConstant::CSMS_STATE_REJECT) {

				// 获取nsq队列配置
//				Nsq::publish('csms.review', array(
//					'cmd' => 'csms.second.verify',
//					'data' => $dirty->toArray(),
//				));
			}

			if ($this->power == 'new') {
				if ($cache_list) {
					foreach ($cache_list as $cache_key => $cache_value) {
						$redis->sRem($redis_key.'-'.$admin, $cache_value['id']);
						$cache_data[$cache_value['id']] = $cache_value['choice'];
					}
					$this->changeCacheCount(CsmsConstant::CSMS_RECHECK, $cache_data);
				}
			}
		}
		return true;
	}


}