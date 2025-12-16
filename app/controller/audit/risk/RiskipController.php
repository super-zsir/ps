<?php
namespace Imee\Controller\Audit\Risk;

use \Imee\Controller\BaseController;
use Imee\Controller\Validation\Audit\RiskIp\CreateValidation;
use Imee\Controller\Validation\Audit\RiskIp\ListValidation;
use Imee\Controller\Validation\Audit\RiskIp\RemoveValidation;
use Imee\Service\Domain\Service\Audit\Risk\RiskIpService;

class RiskipController extends BaseController
{
    /**
     * @page risk.riskip
     * @name 审核系统-风险IP管理
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $service = new RiskIpService();
        $res = $service->getList($this->request->get());
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

	/**
	 * @page risk.riskip
	 * @point 风险IP管理-新增
	 */
	public function createAction()
	{
		CreateValidation::make()->validators($this->request->getPost());
		$service = new RiskIpService();
		$service->create($this->request->getPost());
		return $this->outputSuccess();
	}

	/**
	 * @page risk.riskip
	 * @point 风险IP管理-删除
	 */
	public function removeAction()
	{
		RemoveValidation::make()->validators($this->request->getPost());
		$service = new RiskIpService();
		$service->remove($this->request->getPost());
		return $this->outputSuccess();
	}
}
