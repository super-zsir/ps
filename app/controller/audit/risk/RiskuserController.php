<?php
namespace Imee\Controller\Audit\Risk;

use \Imee\Controller\BaseController;
use Imee\Controller\Validation\Audit\RiskUser\ListValidation;
use Imee\Controller\Validation\Audit\RiskUser\ModifyValidation;
use Imee\Controller\Validation\Audit\RiskUser\RiskDataCountValidation;
use Imee\Controller\Validation\Audit\RiskUser\RiskStrategyStatisticsValidation;
use Imee\Service\Domain\Service\Audit\RiskUser\RiskUserService;

class RiskuserController extends BaseController
{
    /**
     * @page risk.riskUser
     * @name 审核系统-风险用户管理
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        $service = new RiskUserService();
        $res = $service->getList($this->request->get());
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page risk.riskUser
     * @point 修改状态
     */
    public function modifyAction()
    {
        ModifyValidation::make()->validators($this->request->getPost());
        $service = new RiskUserService();
        $service->modify($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page risk.riskUser
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new RiskUserService();
        return $this->outputSuccess($service->getConfig());
    }

	/**
	 * @page risk.riskUser
	 * @point 风险用户审核-数据统计
	 */
    public function riskDataCountAction()
	{
		RiskDataCountValidation::make()->validators($this->request->get());
		$service = new RiskUserService();
		$res = $service->riskDataCount($this->request->get());
		return $this->outputSuccess($res['data'], array('total' => $res['total']));
	}

	/**
	 * @page risk.riskUser
	 * @point 风险用户审核-策略统计
	 */
	public function riskStrategyStatisticsAction()
	{
		RiskStrategyStatisticsValidation::make()->validators($this->request->get());
		$service = new RiskUserService();
		$res = $service->riskStrategyStatistics($this->request->get());
		return $this->outputSuccess($res['data'], array('total' => $res['total']));
	}
}
