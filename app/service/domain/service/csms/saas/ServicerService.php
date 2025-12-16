<?php

namespace Imee\Service\Domain\Service\Csms\Saas;

use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ServicerException;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerOperateContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerSceneContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Saas\Processes\FieldSceneProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\ServicerProcess;
use Imee\Service\Domain\Service\Csms\Saas\Processes\ServicerSceneProcess;

/**
 * 服务商配置服务
 */
class ServicerService extends BaseService
{
    /**
     * @var ServicerListContext
     */
    private $servicerListContext;

    /**
     * @var ServicerOperateContext
     */
    private $servicerOperateContext;

    /**
     * @var ServicerSceneContext
     */
    private $servicerSceneContext;

    /**
     * @var ServicerSceneOperateContext
     */
    private $servicerSceneOperateContext;

    /**
     * @var FeildSceneContext
     */
    private $feildSceneContext;

    /**
     * @var FeildSceneOperateContext
     */
    private $feildSceneOperateContext;

    public function initServicerListContext(array $conditions)
    {
        $this->servicerListContext = new ServicerListContext($conditions);
    }

    public function initServicerOperateContext(array $conditions)
    {
        $this->servicerOperateContext = new ServicerOperateContext($conditions);
    }

    public function initServicerSceneContext(array $conditions)
    {
        $this->servicerSceneContext = new ServicerSceneContext($conditions);
    }

    public function initServicerSceneOperateContext(array $conditions)
    {
        $this->servicerSceneOperateContext = new ServicerSceneOperateContext($conditions);
    }

    public function initFeildSceneContext(array $conditions)
    {
        $this->feildSceneContext = new FeildSceneContext($conditions);
    }

    public function initFeildSceneOperateContext(array $conditions)
    {
        $this->feildSceneOperateContext = new FeildSceneOperateContext($conditions);
    }

    /**
     * 服务商列表
     * @return array
     * @throws \ReflectionException
     */
    public function servicerList(): array
    {
        try {
            $process = new ServicerProcess();
            return $process->servicerList($this->servicerListContext);
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::SERVICER_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新服务商
     * @return bool
     * @throws \ReflectionException
     */
    public function operateServicer(): bool
    {
        try {
            $process = new ServicerProcess();
            if ($this->servicerOperateContext->id > 0) {
                // 更新
                return $process->updateServicer($this->servicerOperateContext);
            }
            // 新建
            return $process->addServicer($this->servicerOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            ServicerException::throwException(ServicerException::SERVICER_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 服务商配置列表
     * @return array
     * @throws \ReflectionException
     */
    public function servicerSceneList(): array
    {
        try {
            $process = new ServicerSceneProcess();
            return $process->servicerSceneList($this->servicerSceneContext);
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::SERVICER_SCENE_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新服务商配置
     * @return bool
     * @throws \ReflectionException
     */
    public function operateServicerScene(): bool
    {
        try {
            $process = new ServicerSceneProcess();
            if ($this->servicerSceneOperateContext->id > 0) {
                // 更新
                return $process->updateServicerScene($this->servicerSceneOperateContext);
            }
            // 新建
            return $process->addServicerScene($this->servicerSceneOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            ServicerException::throwException(ServicerException::SERVICER_SCENE_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 审核字段场景列表
     * @return array
     * @throws \ReflectionException
     */
    public function fieldSceneList(): array
    {
        try {
            $process = new FieldSceneProcess();
            return $process->fieldSceneList($this->feildSceneContext);
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::FIELD_SCENE_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 新增或者更新审核字段场景
     * @return bool
     * @throws \ReflectionException
     */
    public function operateFieldScene(): bool
    {
        try {
            $process = new FieldSceneProcess();
            if ($this->feildSceneOperateContext->id > 0) {
                // 更新
                return $process->updateFieldScene($this->feildSceneOperateContext);
            }
            // 新建
            return $process->addFieldScene($this->feildSceneOperateContext);
        } catch (\Exception $e) {
            if ($e instanceof BaseException) {
                throw $e;
            }
            ServicerException::throwException(ServicerException::FIELD_SCENE_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 删除审核字段场景
     * @return bool
     * @throws \ReflectionException
     */
    public function deleteFieldScene(): bool
    {
        try {
            $process = new FieldSceneProcess();
            if ($this->feildSceneOperateContext->id > 0) {
                // 删除
                return $process->deleteFieldScene($this->feildSceneOperateContext);
            }
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::FIELD_SCENE_DEL_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * @return array|void
     * @throws \ReflectionException
     */
    public function servicers()
    {
        try {
            $process = new ServicerProcess();
            return $process->servicers();
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::SERVICER_LIST_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * @return array|void
     * @throws \ReflectionException
     */
    public function scenes()
    {
        try {
            $process = new ServicerSceneProcess();
            return $process->scenes();
        } catch (\Exception $e) {
            ServicerException::throwException(ServicerException::SERVICER_SCENE_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }
}
