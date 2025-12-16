<?php


namespace Imee\Controller\Operate\Push;


use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Push\PushManagementValidation;
use Imee\Export\Operate\Push\PushDetailExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xsst\XsstPushManagement;
use Imee\Service\Operate\Push\PushManagementService;

class PushmanagementController extends BaseController
{
    use ImportTrait;

    /** @var PushManagementService $service */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new PushManagementService();
    }

    /**
     * @page pushmanagement
     * @name IM管理
     */
    public function mainAction()
    {
    }

    /**
     * @page pushmanagement
     * @point list
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page pushmanagement
     * @point create
     */
    public function createAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsstPushManagement::uploadFields()), [], 'uid_push_manage');
            exit;
        }
        $params = $this->service->initParams($this->params);
        PushManagementValidation::make()->validators($params);
        $data = $this->service->validate($params);
        finish_request_response();
        $this->service->add($data);
    }

    /**
     * @page pushmanagement
     * @point copy content
     */
    public function copyContentAction()
    {
        $this->params['copy_type'] = $this->service::COPY_CONTENT;
        $params = $this->service->initParams($this->params);
        PushManagementValidation::make()->validators($params);
        $data = $this->service->validate($params);
        finish_request_response();
        $this->service->add($data);
    }

    /**
     * @page pushmanagement
     * @point copy list
     */
    public function copyListAction()
    {
        $this->params['copy_type'] = $this->service::COPY_LIST;
        $params = $this->service->initParams($this->params);
        PushManagementValidation::make()->validators($params);
        $data = $this->service->validate($params);
        finish_request_response();
        $this->service->add($data);
    }

    /**
     * @page pushmanagement
     * @point modify
     */
    public function modifyAction()
    {
        $params = $this->service->initParams($this->params);
        PushManagementValidation::make()->validators($params);
        $data = $this->service->validate($params);
        finish_request_response();
        $this->service->edit($data);
    }

    /**
     * @page pushmanagement
     * @point push data
     */
    public function pushAction()
    {
        list($flg, $rec) = $this->service->push($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page pushmanagement
     * @point push rollback
     */
    public function pushRollbackAction()
    {
        list($flg, $rec) = $this->service->pushRollback($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page pushmanagement
     * @point detail list
     */
    public function detailListAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'export') {
            ExportService::addTask($this->uid, 'pushDetailExport.csv', [PushDetailExport::class, 'export'], $this->params, 'pushDetailExport');
            return $this->outputSuccess();
        }
        $data = $this->service->getDetailListAndTotal($this->params);
        return $this->outputSuccess($data['data'], ['total' => $data['total'], 'summary' => $data['summary']]);
    }
}