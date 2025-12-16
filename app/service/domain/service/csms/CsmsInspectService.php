<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Service\Domain\Service\Csms\Validation\Staff\NewTaskValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\OldTaskCheckPowerValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\OldTaskInfoValidation;
use Imee\Service\Domain\Service\Csms\Validation\Staff\TextMachineListValidation;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Staff\NewTaskContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskCheckPowerContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\OldTaskInfoContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineListContext;
use Imee\Service\Domain\Service\Csms\Context\Staff\TextMachineMultpassContext;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\ConfigProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\TaskListProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsinspect\MultPassProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsinspect\NewTaskProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\OldTaskCheckPowerProcess;
use Imee\Service\Domain\Service\Audit\Processes\Csmsaudit\OldTaskInfoProcess;
use Phalcon\Di;


/**
 * 内容管理质检
 * Class CsmsInspectService
 * @package Imee\Service\Domain\Service\Audit\Workbench
 */
class CsmsInspectService
{


	public function getNewTask($newtask)
	{
		NewTaskValidation::make()->validators($newtask);
		$context = new NewTaskContext($newtask);
		$process = new NewTaskProcess($context);
		return $process->handle();
	}


	public function oldTaskInfo($ids)
	{
		OldTaskInfoValidation::make()->validators($ids);
		$context = new OldTaskInfoContext($ids);
		$process = new OldTaskInfoProcess($context);
		return $process->handle();
	}


	public function oldTaskCheckPower($check)
	{
		OldTaskCheckPowerValidation::make()->validators($check);
		$context = new OldTaskCheckPowerContext($check);
		$process = new OldTaskCheckPowerProcess($context);
		return $process->handle();
	}



	public function getTaskList($where = [], $task_ids = [])
	{
		if ($task_ids) {
			$where['ids'] = $task_ids;
		}
        $where['deleted3'] = isset($where['state']) ? $where['state'] : 3;
		$where['is_final'] = true;
		TextMachineListValidation::make()->validators($where);
		$context = new TextMachineListContext($where);
		$process = new TaskListProcess($context);
		return $process->handle();
	}

	public function getCheckedList($where = [])
	{
		return $this->getTaskList($where);
	}


	public function multpass($params = [])
	{
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
        $undo = $redis->hGetAll('CsmsAudit:Deleted3');
        if ($undo) {
            return $undo;
        }
        $undo = CsmsAudit::find([
            'conditions' => 'deleted3 = :deleted: and dateline >= :start:',
            'bind' => [
                'deleted' => CsmsConstant::CSMS_STATE_UNCHECK,
                'start' => strtotime('-7 day')
            ],
            'columns' => 'choice, count(id) as undo',
            'group' => 'choice'
        ])->toArray();
        foreach ($undo as $item) {
            $redis->hset('CsmsAudit:Deleted3', $item['choice'], $item['undo']);
        }
        $redis->expire('CsmsAudit:Deleted3', 600);
        return $redis->hGetAll('CsmsAudit:Deleted3');
    }
}