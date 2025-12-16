<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmsbench;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Operate\Auth\Models\Cms\CmsModuleUser;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Service\Domain\Service\Csms\CsmsBaseService;
use Imee\Service\Domain\Service\Csms\Exception\CsmsWorkbenchException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Helper;
use Phalcon\Di;

class GetTaskListProcess
{

	use CsmsTrait;

	public function __construct($params = [])
	{
		$this->params = $params;
		$this->needAuth = $this->isLeaderPurview() ? 0 : 1;
	}

	public function handle()
	{
		$params = $this->params;
		$needAuth = $this->needAuth;

		$res = ['data' => [], 'total' => 0];
		//$session = Di::getDefault()->getShared('session');
		$appId = isset($params['app_id']) ? $params['app_id'] : 0;
		$admin = isset($params['admin']) ? $params['admin'] : 0;


		//$params['app_ids'] = $this->getAllowAppIds($appId, $admin, $session->get('purview'));
		$params['app_ids'] = $this->getAllowAppIds($appId, $admin, CmsModuleUser::getUserAllAction((int)Helper::getSystemUid()));

		$flag = $this->isValidAppIds($params['app_ids']);
		if (!$flag) {
			return $res;
		}

		$module = isset($params['module']) ?  $params['module'] : '';
		$choice = isset($params['choice']) ? $params['choice'] : '';
		$taskClass = CsmsBaseService::getInstance($module);
		// 旧系统不需要权限验证，直接获取列表
		if (!$needAuth) {
			return $taskClass->getTaskList($params);
		}

		$power = $this->getStaffPower($admin);

		if (!isset($power[$module])) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_POWER_NOTEXIST);
		}
		if (!$power[$module]) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::STAFF_MODULE_CHOICE_NOTEXIST);
		}
		// 获取任务列表，必须待审开始结束时间
		if (!isset($params['begin_time']) || !isset($params['end_time'])) {
			CsmsWorkbenchException::throwException(CsmsWorkbenchException::TASKLIST_TIME_ERROR);
		}

		$redis = new RedisBase(RedisBase::REDIS_ADMIN);
		$redis_key = CsmsConstant::REDIS_STAFF_TASK_PRE.$module.'-'.$choice;

		$oldTask = $redis->sMembers($redis_key.'-'.$admin);
		// 如果没有任务了，返回空，如果有任务，返回对应列表分页
		if (!$oldTask) {
			return $res;
		}
        return $taskClass->getTaskList($params, $oldTask);
	}
}