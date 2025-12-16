<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Export\CommoditySendExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsCommoditySend;
use Imee\Service\Commodity\CommoditySendService;

class CommoditysendController extends BaseController
{
    use ImportTrait;

    /**
     * @var CommoditySendService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CommoditySendService();
    }

    /**
     * @page commoditysend
     * @name 运营系统-物品管理-物品发放管理
     */
    public function mainAction()
    {
    }

    /**
     * @page commoditysend
     * @point 列表
     */
    public function listAction()
    {
        $data = $this->service->getListAndTotal($this->params, $this->params['page'], $this->params['limit']);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page commoditysend
     * @point 创建
     */
    public function createAction()
    {
        [$success, $msg] = $this->service->create($this->params);
        if ($success) {
            if (array_get($this->params, 'flg') == 'check') {
                return $this->outputSuccess($msg);
            }
            return $this->outputSuccess();
        }
        return $this->outputError('-1', $msg);
    }

    /**
     * @page commoditysend
     * @point 审核
     */
    public function auditAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError('-1', 'id 必须');
        }
        if (empty($this->params['state'])) {
            return $this->outputError('-1', 'state 必须');
        }

        $this->service->auditMulti([$this->params['id']], (int)$this->params['state'], (int)$this->params['admin_id']);
        return $this->outputSuccess();
    }

    /**
     * @page commoditysend
     * @point 批量审核
     */
    public function multiAuditAction()
    {
        if (empty($this->params['state']) || !in_array($this->params['state'], [XsCommoditySend::STATE_PASS, XsCommoditySend::STATE_FAIL])) {
            return $this->outputError(-1, '状态不正确');
        }
        $this->params['id'] = is_array($this->params['id']) ? $this->params['id'] : explode(',', $this->params['id']);
        $this->service->auditMulti($this->params['id'] ?? [], (int)$this->params['state'], $this->params['admin_id']);
        return $this->outputSuccess();
    }

    /**
     * @page commoditysend
     * @point 礼物或优惠券审核
     */
    public function giftSendVerifyAction()
    {
        return $this->outputError('-1', 'err');
    }

    /**
     * @page commoditysend
     * @point 批量发放
     */
    public function importAction()
    {
        if (($this->params['c'] ?? '') == 'tpl') {
            (new Csv())->exportToCsv(array_values(XsCommoditySend::uploadFields()), [], 'commoditySend');
            exit;
        }

        [$success, $msg, $data] = $this->uploadCsv(array_keys(XsCommoditySend::uploadFields()));
        if (!$success) {
            return $this->outputError('-1', $msg);
        }

        $data = $data['data'] ?? [];

        if (empty($data)) {
            return $this->outputError('-1', '请填写数据后再上传！');
        }

        [$success, $msg] = $this->service->import($data, $this->params['admin_id']);
        if ($success) {
            return $this->outputSuccess();
        }
        return $this->outputError('-1', $msg);
    }

    /**
     * @page commoditysend
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->request->getQuery();
        $count = $this->service->getTotal($params);
        if ($count > 100000) {
            return $this->outputError('-1', 'Exceeding the maximum limit of 100000');
        }
        return $this->syncExportWork('commoditysend', CommoditySendExport::class, $params);
    }
}