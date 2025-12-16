<?php
namespace Imee\Controller\Audit\Riskcheck;

use \Imee\Controller\BaseController;
use Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck\HistoryValidation;
use Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck\ListValidation;
use Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck\ModifyValidation;

use Imee\Service\Domain\Service\Audit\RiskCheck\ForbiddenCheckService;

class ForbiddencheckController extends BaseController
{
    /**
     * @page riskCheck.forbiddenCheck
     * @name 风控管理-封禁核查
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        
        $service = new ForbiddenCheckService();
        $res = $service->getList(array_merge($this->request->get(), ['isCheckUserForbidden' => true]));
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page riskCheck.forbiddenCheck
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new ForbiddenCheckService();
        return $this->outputSuccess($service->getConfig());
    }

    /**
     * @page riskCheck.forbiddenCheck
     * @point 不解封
     */
    public function modifyAction()
    {
        ModifyValidation::make()->validators($this->request->getPost());
        
        $service = new ForbiddenCheckService();
        $service->modify($this->request->getPost());
        return $this->outputSuccess();
    }

    /**
     * @page riskCheck.forbiddenCheck
     * @point 操作历史
     */
    public function historyAction()
    {
        HistoryValidation::make()->validators($this->request->get());

        $service = new ForbiddenCheckService();
        $result = $service->getHistory($this->request->get());
        return $this->outputSuccess($result['data'], array('total' => $result['total']));
    }
}
