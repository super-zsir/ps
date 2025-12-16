<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Certification\CertificationMaterialsAddValidation;
use Imee\Controller\Validation\Operate\Certification\CertificationMaterialsEditValidation;
use Imee\Service\Operate\Certification\CertificationMaterialsService;

class CertificationmaterialsController extends BaseController
{
    /**
     * @var CertificationMaterialsService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CertificationMaterialsService();
    }

    /**
	 * @page certificationmaterials
	 * @name 运营系统-认证管理-认证素材管理
	 */
    public function mainAction(){}
    
    /**
	 * @page certificationmaterials
	 * @point list
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);

        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
	 * @page certificationmaterials
	 * @point create
     * @logRecord(content = '创建', action = '0', model = 'certificationmaterials', model_id = 'id')
	 */
    public function createAction()
    {
        CertificationMaterialsAddValidation::make()->validators($this->params);
        list($res, $data) = $this->service->add($this->params);

        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page certificationmaterials
	 * @point modify
     * @logRecord(content = '修改', action = '1', model = 'certificationmaterials', model_id = 'id')
	 */
    public function modifyAction()
    {
        CertificationMaterialsEditValidation::make()->validators($this->params);
        list($res, $data) = $this->service->modify($this->params);

        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page certificationmaterials
     * @point info
     */
    public function infoAction()
    {
        $info = $this->service->info($this->params['id'] ?? 0);

        return $this->outputSuccess($info);
    }
}