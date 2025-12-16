<?php


namespace Imee\Controller\Audit\Saas;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditFeildListValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditFeildOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditListValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditStageOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\AuditStageValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\FeildSceneOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\FeildSceneValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\FieldSceneMachineValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\FsMachinOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ProductOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ProductValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ServicerListValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ServicerOperationValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ServicerSceneOperateValidation;
use Imee\Service\Domain\Service\Csms\Validation\Saas\ServicerSceneValidation;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Saas\AuditService;
use Imee\Service\Domain\Service\Csms\Saas\ProductService;
use Imee\Service\Domain\Service\Csms\Saas\ServicerService;

class ConfigurationController extends BaseController
{
    public $params;

    public function onConstruct()
    {
        parent::onConstruct();
        $get = $this->request->getQuery();
        $post = $this->request->getPost();
        $this->params = array_merge(
            ['admin' => $this->uid],
            $get,
            $post
        );
    }

    /**
     * @page saas_setting
     * @name saas管理-审核项配置
     * @point 审核项配置
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function auditListAction()
    {
        AuditListValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditListContext($this->params);
        return $this->outputSuccess($service->auditList());
    }

    /**
     * @page saas_setting
     * @point 新增或编辑审核项
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function operateAuditAction()
    {
        AuditOperateValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditOperatorContext($this->params);
        $service->operateAudit();
        return $this->outputSuccess();
    }

    /**
     * @page saas_setting
     * @point 审核字段配置
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function auditFeildListAction()
    {
        AuditFeildListValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditFeildContext($this->params);
        return $this->outputSuccess($service->auditFeildList());
    }

    /**
     * @page saas_setting
     * @point 新增或编辑审核项字段
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function operateAuditFeildAction()
    {
        AuditFeildOperateValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditFeildOperateContext($this->params);
        $service->operateAuditFeild();
        return $this->outputSuccess();
    }

    /**
     * @page saas_setting
     * @point 有效的审核项列表
     * @return \Phalcon\Http\ResponseInterface
     */
    public function getAuditAction()
    {
        $service = AuditService::getInstance();
        return $this->outputSuccess($service->validAuditList());
    }

    /**
     * @page saas_setting
     * @point 审核项场景配置
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function auditStageListAction()
    {
        AuditStageValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditStageContext($this->params);
        return $this->outputSuccess($service->auditStageList());
    }

    /**
     * @page saas_setting
     * @point 新增或编辑审核项场景
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function operateAuditStageAction()
    {
        AuditStageOperateValidation::make()->validators($this->params);
        $service = AuditService::getInstance();
        $service->initAuditStageOperateContext($this->params);
        $service->operateAuditStage();
        return $this->outputSuccess();
    }

    /**
     * @page saas_service
     * @name saas管理-服务商配置
     * @point 服务商配置
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function servicerListAction()
    {
        ServicerListValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initServicerListContext($this->params);
        return $this->outputSuccess($service->servicerList());
    }

    /**
     * @page saas_service
     * @point 新增或编辑服务商
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     */
    public function operateServicerAction()
    {
        ServicerOperationValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initServicerOperateContext($this->params);
        $service->operateServicer();
        return $this->outputSuccess();
    }

    /**
     * @page saas_service
     * @point 服务商场景配置
     */
    public function servicerSceneListAction()
    {
        ServicerSceneValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initServicerSceneContext($this->params);
        return $this->outputSuccess($service->servicerSceneList());
    }

    /**
     * @page saas_service
     * @point 新增或编辑服务商场景
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function operateServicerSceneAction()
    {
        ServicerSceneOperateValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initServicerSceneOperateContext($this->params);
        $service->operateServicerScene();
        return $this->outputSuccess();
    }

    /**
     * @page saas_setting
     * @point 字段场景配置
     */
    public function fieldSceneListAction()
    {
        FeildSceneValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initFeildSceneContext($this->params);
        return $this->outputSuccess($service->fieldSceneList());
    }

    /**
     * @page saas_setting
     * @point 新增或编辑审核字段场景
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function operateFieldSceneAction()
    {
        FeildSceneOperateValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initFeildSceneOperateContext($this->params);
        $service->operateFieldScene();
        return $this->outputSuccess();
    }

    /**
     * @page saas_setting
     * @point 删除审核字段场景
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function deleteFieldSceneAction()
    {
        FeildSceneOperateValidation::make()->validators($this->params);
        $service = ServicerService::getInstance();
        $service->initFeildSceneOperateContext($this->params);
        $service->deleteFieldScene();
        return $this->outputSuccess();
    }

	/**
	 * @page saas_setting
	 * @point 机审代替人审配置列表
	 */
	public function fieldSceneMachineListAction()
	{
		FieldSceneMachineValidation::make()->validators($this->params);
		$service = AuditService::getInstance();
		$service->initMachine($this->params);
		return $this->outputSuccess($service->fieldMachineList());
	}

	/**
	 * @page saas_setting
	 * @point 新增或编辑机审代替人审配置
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws \Imee\Exception\CommonException
	 * @throws \ReflectionException
	 */
	public function operateFieldSceneMachineAction()
	{
		FsMachinOperateValidation::make()->validators($this->params);
		$service = AuditService::getInstance();
		$service->initFsMachine($this->params);
		$service->operateFsMachine();
		return $this->outputSuccess();
	}

	/**
	 * @page saas_setting
	 * @point 新增或编辑机审代替人审配置
	 * @return \Phalcon\Http\ResponseInterface
	 * @throws \ReflectionException
	 */
	public function configAction()
	{
		$service = AuditService::getInstance();
		return $this->outputSuccess($service->config());
	}

    /**
     * @page saas_product
     * @name saas管理-产品配置
     * @point 产品配置
     */
    public function productListAction()
    {
        ProductValidation::make()->validators($this->params);
        $service = ProductService::getInstance();
        $service->initProductContext($this->params);
        return $this->outputSuccess($service->productList());
    }

    /**
     * @page saas_product
     * @point 新增或编辑产品
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Imee\Exception\CommonException
     * @throws \ReflectionException
     */
    public function operateProductAction()
    {
        ProductOperateValidation::make()->validators($this->params);
        $service = ProductService::getInstance();
        $service->initProductOperateContext($this->params);
        $service->operateProduct();
        return $this->outputSuccess();
    }

    /**
     * @page saas_setting
     * @point 全部服务商
     * @return \Phalcon\Http\ResponseInterface
     * @throws \ReflectionException
     */
    public function servicersAction()
    {
        $service = ServicerService::getInstance();
        return $this->outputSuccess($service->servicers());
    }

    /**
     * @page saas_setting
     * @point 全部字段
     * @return \Phalcon\Http\ResponseInterface
     * @throws \ReflectionException
     */
    public function fieldsAction()
    {
        $service = AuditService::getInstance();
        return $this->outputSuccess($service->fields());
    }

    /**
     * @page saas_setting
     * @point 全部服务商场景
     * @return \Phalcon\Http\ResponseInterface
     * @throws \ReflectionException
     */
    public function scenesAction()
    {
        $service = ServicerService::getInstance();
        return $this->outputSuccess($service->scenes());
    }

    /**
     * @page saas_setting
     * @point 全部app
     * @return \Phalcon\Http\ResponseInterface
     * @throws \ReflectionException
     */
    public function productsAction()
    {
        $service = ProductService::getInstance();
        return $this->outputSuccess($service->products());
    }
}