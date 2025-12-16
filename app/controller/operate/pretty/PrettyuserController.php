<?php

namespace Imee\Controller\Operate\Pretty;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pretty\User\ListValidation;
use Imee\Controller\Validation\Operate\Pretty\User\ExportValidation;

use Imee\Controller\Validation\Operate\Pretty\User\CreateValidation;
use Imee\Controller\Validation\Operate\Pretty\User\ModifyValidation;
use Imee\Controller\Validation\Operate\Pretty\User\ExpireValidation;

use Imee\Service\Domain\Service\Pretty\PrettyuserService;
use Imee\Export\PrettyuserExport;

class PrettyuserController extends BaseController
{
    /**
     * @var PrettyuserService $service
     */
    private $service;
    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PrettyuserService();
    }

    /**
     * @page prettyuser
     * @name -运营靓号
     */
    public function mainAction()
    {
    }

    /**
     * @page prettyuser
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->trimParams($this->request->get());

        ListValidation::make()->validators($params);
        $res = $this->service->getList($params);
        return $this->outputSuccess($res['data'], array('total' => $res['total']));
    }

    /**
     * @page prettyuser
     * @point 创建
     */
    public function createAction()
    {
        $params = $this->trimParams($this->request->getPost());
        $params = $this->service->handleParams($params);
        CreateValidation::make()->validators($params);
        $this->service->create($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettyuser
     * @point 修改
     */
    public function modifyAction()
    {
        $params = $this->trimParams($this->request->getPost());
        $params = $this->service->handleParams($params);
        ModifyValidation::make()->validators($params);
        $this->service->modify($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettyuser
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->trimParams($this->request->get());

        ExportValidation::make()->validators($params);
        $this->params['guid'] = 'prettyuser';
        ExportService::addTask($this->uid, 'prettyuser.xlsx', [PrettyUserExport::class, 'export'], $this->params, '运营靓号导出');
        ExportService::showHtml();

        return $this->outputSuccess();
    }

    /**
     * @page prettyuser
     * @point 提前到期
     */
    public function expireAction()
    {
        $params = $this->trimParams($this->request->get());

        ExpireValidation::make()->validators($params);
        $this->service->expire($params);
        return $this->outputSuccess();
    }

    /**
     * @page prettyuser
     * @point 1位&2位数靓号
     */
    public function lengthAction()
    {
    }

    // /**
    //  * @page prettyuser
    //  * @point 历史记录
    //  */
    // public function historyAction()
    // {
    //     $params = $this->trimParams($this->request->get());

    //     HistoryValidation::make()->validators($params);

    //     $res = $this->service->getHistory($params);
    //     return $this->outputSuccess($res['data'], array('total' => $res['total']));
    // }
}
