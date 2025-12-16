<?php

namespace Imee\Controller\Operate\Commodity;

use Imee\Controller\BaseController;
use Imee\Export\CommodityListExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Commodity\CommodityService;
use Imee\Controller\Validation\Operate\Commodity\AddValidation;

class CommodityController extends BaseController
{
    use ImportTrait;

    /**
     * @var CommodityService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new CommodityService();
    }


    /**
     * @page commodity
     * @name 运营系统 - 物品管理 - 物品列表
     * @point 物品列表
     */
    public function listAction()
    {
        $result = $this->service->getListAndTotal(
            $this->params, 'cid desc', $this->params['page'], $this->params['limit']
        );
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 下拉选项
     */
    public function optionsAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'gift_info') {
            if (empty($this->params['ext_id']) || !is_numeric($this->params['ext_id']) || $this->params['ext_id'] < 1) {
                return $this->outputSuccess([]);
            }
            $result = $this->service->getGiftInfo((int)$this->params['ext_id']);
            return $this->outputSuccess($result);
        } elseif ($c == 'group_info') {
            if (empty($this->params['group_id']) || !is_numeric($this->params['group_id']) || $this->params['group_id'] < 1) {
                return $this->outputSuccess([]);
            }
            $result = $this->service->getGroupInfo((int)$this->params['group_id']);
            return $this->outputSuccess($result);
        } elseif ($c == 'group_search') {
            if (empty($this->params['str'])) {
                return $this->outputSuccess([]);
            }
            $result = $this->service->getGroupList(trim($this->params['str']));
            return $this->outputSuccess($result);
        }
        $result = $this->service->getOptions(APP_ID);
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 优惠券下拉
     */
    public function loadCouponTypeAction()
    {
        $result = $this->service->getCouponTypeList($this->params['type'] ?? '');
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 优惠券物品下拉
     */
    public function loadExtIdAction()
    {
        if (empty($this->params['type']) || empty($this->params['coupon_type'])) {
            return $this->outputError(-1, '请传递物品类型type和优惠券类型coupon_type');
        }
        $result = $this->service->getLoadExtId($this->params['type'], $this->params['coupon_type'], APP_ID);
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 物品类型关联下拉属性
     */
    public function loadOptionByTypeAction()
    {
        if (empty($this->params['type'])) {
            return $this->outputError(-1, '请传递物品类型type');
        }
        if (($this->params['c'] ?? '') == 'disabled') {
            return $this->outputSuccess($this->service->getDisabledByType($this->params['type']));
        }
        $result = $this->service->getOptinsByType($this->params['type']);
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 添加物品
     */
    public function addAction()
    {
        AddValidation::make()->validators($this->params);
        list($result, $data) = $this->service->add($this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page commodity
     * @point 修改物品
     */
    public function editAction()
    {
        AddValidation::make()->validators($this->params);
        if (!isset($this->params['image_bg'])) {
            $this->params['image_bg'] = '';
        }
        if (empty($this->params['cid'])) {
            return $this->outputError(-1, 'cid缺失');
        }
        list($result, $data) = $this->service->edit($this->params['cid'], $this->params);
        if (!$result) {
            return $this->outputError(-1, $data);
        }
        return $this->outputSuccess($data);
    }

    /**
     * @page commodity
     * @point 审核物品
     */
    public function reviewAction()
    {
        if (empty($this->params['cid'])) {
            return $this->outputError(-1, 'cid不能为空！');
        }

        if (empty($this->params['state'])) {
            return $this->outputError(-1, 'state不能为空！');
        }

        list($result, $msg) = $this->service->review($this->params['cid'], $this->params['state'], $this->params['admin_id']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($result);
    }

    /**
     * @page commodity
     * @point 批量审核通过
     */
    public function reviewBatchAction()
    {
        list($result, $msg) = $this->service->reviewPassBatch($this->params);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }

        return $this->outputSuccess($this->params);
    }

    /**
     * @page commodity
     * @point 导出物品
     */
    public function exportAction()
    {
        return $this->syncExportWork('commodityExport', CommodityListExport::class, $this->params);
    }

    /**
     * @page commodity
     * @point 导出SQL
     */
    public function exportSqlAction()
    {
        $this->service->exportSql($this->params);
    }

    /**
     * @page commodity
     * @point 操作日志
     */
    public function logAction()
    {
        if (empty($this->params['cid'])) {
            return $this->outputError(-1, 'cid不能为空！');
        }
        $result = $this->service->getLogListAndTotal(
            $this->params, 'id desc'
        );
        return $this->outputSuccess($result['data'], ['total' => $result['total']]);
    }

    /**
     * @page commodity
     * @point 详情
     */
    public function detailAction()
    {
        if (empty($this->params['cid'])) {
            return $this->outputError(-1, 'cid不能为空！');
        }

        if (empty($this->params['type'])) {
            return $this->outputError(-1, 'type不能为空！');
        }

        if ($this->params['type'] == 'edit') {
            $result = $this->service->getEditInfo($this->params['cid']);
        } elseif ($this->params['type'] == 'edit2') {
            $result = $this->service->getInfoOption((int)$this->params['cid']);
        } else {
            $result = $this->service->getInfo($this->params['cid'], $this->params['type']);
        }

        return $this->outputSuccess($result);
    }
}