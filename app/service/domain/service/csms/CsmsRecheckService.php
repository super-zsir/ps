<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Validation\Staff\NewTaskValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\OldTaskCheckPowerValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\OldTaskInfoValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\TextMachineListValidation;
use Imee\Service\Domain\Service\Csms\Validation\Text\MultPassValidation;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Staff\NewTaskContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskCheckPowerContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskInfoContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineListContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineMultpassContext;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsrecheck\MultPassProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\OldTaskCheckPowerProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\OldTaskInfoProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\TaskListProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsrecheck\NewTaskProcess;
use Phalcon\Di;


/**
 * 内容管理系统 - 复审
 * Class CsmsRecheckService
 * @package Imee\Service\Domain\Service\Audit\Workbench
 */
class CsmsRecheckService
{


	/**
	 * 获取任务
	 * @param array $newtask
	 * @return array
	 * @throws \Imee\Exception\CommonException
	 */
	public function getNewTask(array $newtask)
	{
		NewTaskValidation::make()->validators($newtask);
		$context = new NewTaskContext($newtask);
		$process = new NewTaskProcess($context);
		return $process->handle();
	}


	/**
	 * 旧任务信息
	 * @param array $ids
	 * @return array
	 * @throws \Imee\Exception\CommonException
	 */
	public function oldTaskInfo(array $ids)
	{
		OldTaskInfoValidation::make()->validators($ids);
		$context = new OldTaskInfoContext($ids);
		$process = new OldTaskInfoProcess($context);
		return $process->handle();
	}

	/**
	 * 检查权限
	 * @param array $check
	 * @return bool
	 * @throws \Imee\Exception\CommonException
	 */
	public function oldTaskCheckPower(array $check)
	{
		OldTaskCheckPowerValidation::make()->validators($check);
		$context = new OldTaskCheckPowerContext($check);
		$process = new OldTaskCheckPowerProcess($context);
		return $process->handle();
	}


	/**
	 * 列表
	 * @param array $where
	 * @param array $task_ids
	 * @return array
	 * @throws \Imee\Exception\CommonException
	 */
	public function getTaskList(array $where = [], $task_ids = [])
	{
		if ($task_ids) {
			$where['ids'] = $task_ids;
		}
        $where['deleted2'] = isset($where['state']) ? $where['state'] : 3;
		TextMachineListValidation::make()->validators($where);
		$where['is_second'] = true;
		$context = new TextMachineListContext($where);
		$process = new TaskListProcess($context);
		return $process->handle();
	}

	/**
	 * 获取已审列表
	 * @param array $where
	 * @return array
	 */
	public function getCheckedList($where = [])
	{
		return $this->getTaskList($where);
	}

	public function multpass($params = [])
	{
		MultPassValidation::make()->validators($params);
		$context = new TextMachineMultpassContext($params);
		$process = new MultPassProcess($context);
		return $process->handle();
	}

	public function getConfig($params = [])
	{
		$process = new ConfigProcess();
		return $process->handle($params);
	}

    public function moduleStatistics()
    {
        $redis = Di::getDefault()->getShared('redis');
        $undo = $redis->hGetAll('CsmsAudit:Deleted2');
        if ($undo) {
            return $undo;
        }
        $undo = CsmsAudit::find([
            'conditions' => 'deleted2 = :deleted: and dateline >= :start:',
            'bind' => [
                'deleted' => CsmsConstant::CSMS_STATE_UNCHECK,
                'start' => strtotime('-7 day')
            ],
            'columns' => 'choice, count(id) as undo',
            'group' => 'choice'
        ])->toArray();
        foreach ($undo as $item) {
            $redis->hset('CsmsAudit:Deleted2', $item['choice'], $item['undo']);
        }
        $redis->expire('CsmsAudit:Deleted2', 600);
        return $redis->hGetAll('CsmsAudit:Deleted2');
    }
}