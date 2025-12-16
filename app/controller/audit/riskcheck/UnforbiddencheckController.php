<?php
namespace Imee\Controller\Audit\Riskcheck;

use \Imee\Controller\BaseController;

use Imee\Controller\Validation\Audit\RiskCheck\ForbiddenCheck\ListValidation;

use Imee\Service\Domain\Service\Audit\RiskCheck\ForbiddenCheckService;

class UnforbiddencheckController extends BaseController
{
    /**
     * @page riskCheck.unforbiddenCheck
     * @name 风控管理-解封核查
     * @point 列表
     */
    public function indexAction()
    {
        ListValidation::make()->validators($this->request->get());
        // $context = new ListContext($this->request->get());
        $service = new ForbiddenCheckService();
        $res = $service->getList($this->request->get());
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page riskCheck.unforbiddenCheck
     * @point 配置数据
     */
    public function configAction()
    {
        $service = new ForbiddenCheckService();
        return $this->outputSuccess($service->getConfig());
    }
}
