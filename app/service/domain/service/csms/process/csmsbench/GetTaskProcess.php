<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisLock;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsTasklimit;
use Imee\Service\Domain\Service\Audit\Workbench\BaseService;
use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Helper;
use Phalcon\Di;

class GetTaskProcess
{

	use CsmsTrait;

	public function __construct($params = [])
	{
		$this->params = $params;
		$this->needAuth = $this->isLeaderPurview() ? 0 : 1;
	}

	public function handle()
	{
		$admin = isset($this->params['admin']) ? $this->params['admin'] : 0;
		$module = isset($this->params['module']) ? $this->params['module'] : '';
		$choice = isset($this->params['choice']) ? $this->params['choice'] : '';
		$type = isset($this->params['type']) ? $this->params['type'] : '';

		$needAuth = $this->needAuth;

		// 类型工作台 或者 审核项工作台
		if (!$module || (!$choice && !$type)) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASKLIST_PARAM_ERROR);
		}

		$power = $this->getStaffPower($admin);

		if (!isset($power[$module])) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_POWER_NOTEXIST);
		}
		if (!$power[$module]) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_CHOICE_NOTEXIST);
		}

		// 审核项权限
        $powerChoice = $power[$module];
        if($type){
            if($choice){
                $choices = in_array($choice, $powerChoice) ? [$choice] : [];
            }else{
                $choices = $powerChoice;
            }
        }else{
            if($choice){
                $choices = in_array($choice, $powerChoice) ? [$choice] : [];
            }else{
                // 审核项模式 - 审核项必传
                $choices = [];
            }
        }
        $this->params['choices'] = $choices;

        // 无审核项权限
        if(!$choices){
            CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_CHOICE_NONE);
        }

		$redis = new RedisBase(RedisBase::REDIS_ADMIN);
		$redisKey = CsmsConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;
		$redisAdminKey = $redisKey.'-'.$admin;

		$oldTask = $redis->sMembers($redisAdminKey);
		$taskClass = CsmsBaseService::getInstance($module);

		// 旧系统不需要权限验证，直接获取列表
		if (!$needAuth) {
			return $taskClass->getTaskList($this->params);
		}


		// 如果有旧任务，检查是否已经取消权限
		if ($oldTask) {
			$oldInfo = $taskClass->oldTaskInfo(['ids' => $oldTask]);
			foreach ($oldTask as $oldKey => $oldValue) {
				$isPower = $taskClass->oldTaskCheckPower([
					'power' => $power[$module],
					'info' => $oldInfo[$oldValue]
				]);
				if (!$isPower) {
					// 没有权限，则删除旧任务
					$keyIndex = array_search($oldValue, $oldTask);
					unset($oldTask[$keyIndex]);
					$redis->Srem($redisAdminKey, $oldValue);
				}
			}
		}
		// 如果还有旧任务，直接返回
		if ($oldTask) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_HAS_TASK, ['data' => $oldTask]);
		} else {
			$redis->del($redisAdminKey);
		}
		// 获取新任务
		$oldIds = [];
		$moduleUserKey = CsmsConstant::REDIS_MODULE_USER.$module.'-'.$choice;
		$moduleUsers = $redis->sMembers($moduleUserKey);
		if ($moduleUsers) {
			foreach ($moduleUsers as $user) {
				$userTask = $redis->sMembers($redisKey.'-'.$user);
				if ($userTask) {
					$oldIds = array_merge($oldIds, $userTask);
				} else {
					$redis->sRem($moduleUserKey, $user);
				}
			}
		}

		$keyLock = CsmsConstant::REDIS_TASK_LOCK_PRE.$module;
		$redisLock = new RedisLock(RedisBase::REDIS_ADMIN, true, 0, 0);
		$r = $redisLock->lock($keyLock, CsmsConstant::REDIS_TASK_LOCK_TTL);
		if (!$r) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::REDIS_TASK_LOCK);
		}

		//$session = Di::getDefault()->getShared('session');
		//$purview = $session->get('purview');
		$purview = CmsModuleUser::getUserAllAction((int)Helper::getSystemUid());
		$appId = isset($params['app_id']) ? $params['app_id'] : 0;
		$params['app_ids'] = $this->getAllowAppIds($appId, $admin, $purview);


		$flag = $this->isValidAppIds($params['app_ids']);
		if (!$flag) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_HAS_NOT_APP);
		}

		$limit = CsmsTasklimit::findFirst([
			'conditions'  => 'module = :module: and choice = :choice:',
			'bind' => [
				'module' => $module,
				'choice' => $choice
			]
		]);

		$taskNumber = $limit ? ($limit->number ? $limit->number : CsmsConstant::TASK_DEFAULT_NUMBER) : CsmsConstant::TASK_DEFAULT_NUMBER;

		$newTask = $taskClass->getNewTask([
			'old_ids' => $oldIds,
			'power' => $choices,
			'num' => $taskNumber,
			'where' => $params
		]);


		if (!$newTask) {
			$redisLock->unlock($keyLock);
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::NOT_HAS_NEW_TASK);
		} else {
			foreach ($newTask as $newKey => $newValue) {
				$redis->sAdd($redisAdminKey, $newValue);
			}
			$redis->expire($redisAdminKey, CsmsConstant::REDIS_TASK_TTL);
		}
		$redis->sAdd($moduleUserKey, $admin);
		$redisLock->unlock($keyLock);
		return $newTask;

	}

}