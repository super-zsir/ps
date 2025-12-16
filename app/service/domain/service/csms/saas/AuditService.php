<?php

namespace Imee\Service\Domain\Service\Csms\Saas;

use Imee\Service\Domain\Service\Csms\Context\Saas\FieldSceneMachineContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FsMachineContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\AuditDbException;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditFeildListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditFeildOperateContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditOperatorContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditStageContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditStageOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;
use Imee\Service\Domain\Service\Csms\Saas\Processes\AuditFieldProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\AuditProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\AuditStageProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\FieldSceneMachineProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\FieldSceneProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\ServicerProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\ServicerSceneProcess;

/**
 * 审核项配置服务
 */
class AuditService extends BaseService
{
    /**
     * @var AuditListContext
     */
    private $auditListContext;

    /**
     * @var AuditOperatorContext
     */
    private $auditOperatorContext;

    /**
     * @var AuditFeildListContext
     */
    private $auditFeildListContext;

    /**
     * @var AuditFeildOperateContext
     */
    private $auditFeildOperateContext;

    /**
     * @var AuditStageContext
     */
    private $auditStageContext;

    /**
     * @var AuditStageOperateContext
     */
    private $auditStageOperateContext;

	/**
	 * @var FieldSceneMachineContext
	 */
	private $machineContext;

	/**
	 * @var FsMachineContext
	 */
	private $fsMachineContext;


    /**
     * 初始化
     * @param array $params
     * @return void
     */
    public function initAuditListContext(array $params)
    {
        $this->auditListContext = new AuditListContext($params);
    }

    /**
     * 初始化
     * @param array $params
     * @return void
     */
    public function initAuditOperatorContext(array $params)
    {
        $this->auditOperatorContext = new AuditOperatorContext($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function initAuditFeildContext(array $params)
    {
        $this->auditFeildListContext = new AuditFeildListContext($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function initAuditFeildOperateContext(array $params)
    {
        $this->auditFeildOperateContext = new AuditFeildOperateContext($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function initAuditStageContext(array $params)
    {
        $this->auditStageContext = new AuditStageContext($params);
    }

    /**
     * @param array $params
     * @return void
     */
    public function initAuditStageOperateContext(array $params)
    {
        $this->auditStageOperateContext = new AuditStageOperateContext($params);
    }

	/**
	 * @param array $params
	 * @return void
	 */
	public function initMachine(array $params)
	{
		$this->machineContext = new FieldSceneMachineContext($params);
	}

	public function initFsMachine(array $params)
	{
		$this->fsMachineContext = new FsMachineContext($params);
	}

    /**
     * 审核项列表
     * @return array
     * @throws \ReflectionException
     */
    public function auditList(): array
    {
        try {
            $process = new AuditProcess();
            return $process->auditList($this->auditListContext);
        } catch (\Exception $e) {
            AuditDbException::throwException(AuditDbException::AUDIT_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新审核项
     * @return bool
     * @throws \ReflectionException
     */
    public function operateAudit(): bool
    {
        try {
            $process = new AuditProcess();
            if ($this->auditOperatorContext->id > 0) {
                // 更新
                return $process->updateAudit($this->auditOperatorContext);
            }
            // 新建
            return $process->addAudit($this->auditOperatorContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            AuditDbException::throwException(AuditDbException::AUDIT_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 审核项字段列表
     * @return array
     * @throws \ReflectionException
     */
    public function auditFeildList(): array
    {
        try {
            $process = new AuditFieldProcess();
            return $process->auditFeildList($this->auditFeildListContext);
        } catch (\Exception $e) {
            AuditDbException::throwException(AuditDbException::AUDIT_FEILD_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新审核项字段
     * @return bool
     * @throws \ReflectionException
     */
    public function operateAuditFeild(): bool
    {
        try {
            $process = new AuditFieldProcess();
            if ($this->auditFeildOperateContext->id > 0) {
                // 更新
                return $process->updateAuditFeild($this->auditFeildOperateContext);
            }
            // 新建
            return $process->addAuditFeild($this->auditFeildOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            AuditDbException::throwException(AuditDbException::AUDIT_FEILD_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * @return array
     */
    public function validAuditList(): array
    {
        $process = new AuditProcess();
        return $process->getValidAudit();
    }

    /**
     * 审核项场景列表
     * @return array
     * @throws \ReflectionException
     */
    public function auditStageList(): array
    {
        try {
            $process = new AuditStageProcess();
            return $process->auditStageList($this->auditStageContext);
        } catch (\Exception $e) {
            AuditDbException::throwException(AuditDbException::AUDIT_STAGE_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新审核项场景
     * @return bool
     * @throws \ReflectionException
     */
    public function operateAuditStage(): bool
    {
        try {
            if (strlen($this->auditStageOperateContext->info) > 200) {
                AuditDbException::throwException(AuditDbException::AUDIT_INFO_TOO_LONG);
            }
            $process = new AuditStageProcess();
            if ($this->auditStageOperateContext->id > 0) {
                // 更新
                return $process->updateAuditStage($this->auditStageOperateContext);
            }
            // 新建
            return $process->addAuditStage($this->auditStageOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            AuditDbException::throwException(AuditDbException::AUDIT_STAGE_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function fields(): array
    {
        try {
            $process = new AuditFieldProcess();
            return $process->fields();
        } catch (\Exception $e) {
            AuditDbException::throwException(AuditDbException::AUDIT_FEILD_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

	/**
	 * 机审代替人审配置
	 * @return array
	 * @throws \ReflectionException
	 */
	public function fieldMachineList(): array
	{
		try {
			$process = new FieldSceneMachineProcess();
			return $process->fieldSceneMachineList($this->machineContext);
		} catch (\Exception $e) {
			AuditDbException::throwException(AuditDbException::MACHINE_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
		}
	}

	/**
	 * 新增或者更新机审代替人审
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function operateFsMachine(): bool
	{
		try {
			$process = new FieldSceneMachineProcess();
			if ($this->fsMachineContext->id > 0) {
				// 更新
				return $process->updateFieldSceneMachine($this->fsMachineContext);
			}
			// 新建
			return $process->addFieldSceneMachine($this->fsMachineContext);
		} catch (\Exception $e) {
			if ($e instanceof BaseException) {
				throw $e;
			}
			AuditDbException::throwException(AuditDbException::MACHINE_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
		}
	}

	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	public function config(): array
	{
		try {
			$server = new ServicerSceneProcess();
			$config['scenes'] = $server->scenes();
			$fs = new FieldSceneProcess();
			$config['fieldScene'] = $fs->fieldScenes();
			$config['valid_state'] = array(
				['label' => '通过', 'value' => 1],
				['label' => '拒绝', 'value' => 2],
				['label' => '未识别', 'value' => 3],
			);
			$config['type_other'] = array(
				['label' => '否', 'value' => 1],
				['label' => '是', 'value' => 2],
			);
			$config['state'] = array(
				['label' => '正常', 'value' => 1],
				['label' => '下线', 'value' => 2],
			);
			return $config;
		} catch (\Exception $e) {
			AuditDbException::throwException(AuditDbException::CONFIG_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
		}
	}
}