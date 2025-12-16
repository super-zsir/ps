<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Certification\CertificationInfoEditValidation;
use Imee\Service\Operate\Certification\CertificationInfoService;

class CertificationinfoController extends BaseController
{

    /**
     * @var CertificationInfoService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CertificationInfoService();
    }
    /**
	 * @page certificationinfo
	 * @name 运营系统-认证管理-认证信息管理
	 */
    public function mainAction(){}
    
    /**
	 * @page certificationinfo
	 * @point list
	 */
    public function listAction()
    {
        $list = $this->service->getList($this->params);

        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }

    /**
	 * @page certificationinfo
	 * @point modify
     * @logRecord(content = '修改', action = '1', model = 'certificationinfo', model_id = 'id')
	 */
    public function modifyAction()
    {
        CertificationInfoEditValidation::make()->validators($this->params);
        list($res, $data) = $this->service->modify($this->params);

        if (!$res) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }
    
    /**
	 * @page certificationinfo
	 * @point delete
     * @logRecord(content = '删除', action = '2', model = 'certificationinfo', model_id = 'id')
	 */
    public function deleteAction(){}
}