<?php

namespace Imee\Controller\Operate\Gift;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Gift\GiftCreateValidation;
use Imee\Export\Operate\GiftExport;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Redis\GiftVersionRedis;
use Imee\Service\Helper;
use Imee\Service\Operate\Gift\GiftService;

class GiftController extends BaseController
{
    use ImportTrait;

    /** @var GiftService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new GiftService();
    }

    /**
     * @page gift
     * @name 礼物管理
     */
    public function mainAction()
    {
    }

    /**
     * @page gift
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == '_secret_gift') {
            $tabId = $this->params['tab_id'] ?? 0;
            if ($tabId == 11) {
                return $this->outputSuccess(['is_secret_gift' => "1"]);
            }
            return $this->outputSuccess(['is_secret_gift' => "0"]);
        }
        $data = $this->service->getListAndTotal($this->params, ($this->params['sort'] ?? 'id') . ' ' . ($this->params['dir'] ?? 'desc'), $this->params['page'] ?? 1, $this->params['limit'] ?? 15);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page gift
     * @point 获取tab数据源
     */
    public function tabchangeAction()
    {
        $tabId = $this->params['tab_id'] ?? 0;
        if ($tabId == 11) {
            return $this->outputSuccess(['is_secret_gift' => "1"]);
        }
        return $this->outputSuccess(['is_secret_gift' => "0"]);
    }

    /**
     * @page gift
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'gift', model_id = 'id')
     */
    public function createAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == '_in_bind_box') {
            $gifts = [];
            for ($i = 0; $i < 5; $i++) {
                $gifts[] = ['gift_id' => '', 'rare_type' => ''];
            }
            return $this->outputSuccess(['gifts' => $gifts]);
        } elseif ($c == 'blind_gift') {
            $str = $this->params['str'] ?? '';
            if (!$str) {
                return $this->outputSuccess();
            }
            $gift = $this->service->getGiftSearchMap($str);
            return $this->outputSuccess($gift);
        }
        $data = $this->service->initGift($this->params);
        GiftCreateValidation::make()->validators($data);

        $data = $this->service->create($data);
        return $this->outputSuccess($data);
    }

    /**
     * @page gift
     * @point 编辑
     * @logRecord(content = '编辑', action = '1', model = 'gift', model_id = 'id')
     */
    public function modifyAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'blind_gift') {
            $str = $this->params['str'] ?? '';
            if (!$str) {
                return $this->outputSuccess();
            }
            $gift = $this->service->getGiftSearchMap($str);
            return $this->outputSuccess($gift);
        }

        $data = $this->service->initGift($this->params, true);
        GiftCreateValidation::make()->validators($data);

        $data = $this->service->modify($data);
        return $this->outputSuccess($data);
    }

    /**
     * @page gift
     * @point 更新版本
     * @logRecord(content = '更新版本', action = '1', model = 'gift', model_id = 'id')
     */
    public function updateVersionAction()
    {
        $data = GiftVersionRedis::update();
        return $this->outputSuccess(['id' => 0, 'before_json' => [GiftVersionRedis::KEY => $data['before']], 'after_json' => [GiftVersionRedis::KEY => $data['after']]]);
    }

    /**
     * @page gift
     * @point 上下架
     * @logRecord(content = '上下架', action = '3', model = 'gift', model_id = 'id')
     */
    public function reviewAction()
    {
        [$result, $msg] = $this->service->review($this->params['id'], $this->params['deleted']);
        if (!$result) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess($result);
    }

    /**
     * @page gift
     * @point 上传图片
     * @logRecord(content = '上传图片', action = '1', model = 'gift', model_id = 'id')
     */
    public function uploadAction()
    {
        $data = $this->service->upload($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page gift
     * @point 获取详情
     */
    public function infoAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'id 必须');
        }
        $c = $this->request->getQuery('c', 'trim', '');
        if ($c == 'show') {
            return $this->outputSuccess($this->service->getShowInfo((int)$this->params['id']));
        } elseif ($c == 'property') {
            return $this->outputSuccess($this->service->getPropertyInfo((int)$this->params['id']));
        } elseif ($c == 'upload_info') {
            $data = $this->service->getUploadInfo($this->params['id']);
            $resp = [];
            foreach ($data as $key => $val) {
                $resp[$key] = $val;
                $resp[$key . '_lesscode_all_url'] = Helper::getHeadUrl($val);
            }
            return $this->outputSuccess($resp);
        }

        return $this->outputSuccess($this->service->getInfo((int)$this->params['id'], 'edit'));
    }

    /**
     * @page gift
     * @point 描述管理
     * @logRecord(content = '描述管理', action = '1', model = 'gift', model_id = 'id')
     */
    public function propertyAction()
    {
        $data = $this->service->property($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page gift
     * @point 批量修改tab
     */
    public function modifyTabAction()
    {
        list($result, $msg, $data) = $this->uploadCsv(['gid']);
        if (!$result || empty($data['data'])) {
            return $this->outputError(-1, $msg ?: '请上传数据');
        }

        $gids = array_column($data['data'], 'gid');
        $tabId = intval($this->params['tab_id'] ?? 0);

        $this->service->modifyTab($tabId, $gids);
        return $this->outputSuccess();
    }

    /**
     * @page gift
     * @point 导出SQL
     */
    public function exportSqlAction()
    {
        $this->service->exportSql();
    }

    /**
     * @page gift
     * @point 导出
     */
    public function exportAction()
    {
        $this->params['guid'] = 'gift';
        ExportService::addTask($this->uid, 'gift.xlsx', [GiftExport::class, 'export'], $this->params, '礼物导出');
        ExportService::showHtml();
    }
}