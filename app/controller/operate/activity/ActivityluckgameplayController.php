<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\ActivityLuckGamePlayNewValidation;
use Imee\Controller\Validation\Operate\Activity\ActivityLuckGamePlayRewardValidation;
use Imee\Controller\Validation\Operate\Activity\ActivityLuckGamePlayValidation;
use Imee\Export\Operate\Activity\ActivityLuckGamePlayExport;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Operate\Activity\ActivityLuckGamePlayService;

class ActivityluckgameplayController extends BaseController
{
    /**
     * @var ActivityLuckGamePlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityLuckGamePlayService();
    }

    /**
     * @page activityluckgameplay
     * @name 幸运玩法
     */
    public function mainAction()
    {
    }

    /**
     * @page activityluckgameplay
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            $options = $this->service->getOptions();
            return $this->outputSuccess($options);
        } else if ($c == 'award') {
            $map = $this->service->getAwardOptions();
            return $this->outputSuccess($map);
        }
        $this->params['types'] = [BbcTemplateConfig::TYPE_WHEEL_LOTTERY];
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page activityluckgameplay
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'activityluckgameplay', model_id = 'id')
     */
    public function createAction()
    {
        ActivityLuckGamePlayNewValidation::make()->validators($this->params);
        [$res, $id] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page activityluckgameplay
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'activityluckgameplay', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($this->params['id']) && empty($this->params['id'])) {
            return $this->outputError(-1, '数据错误');
        }
        if (BbcTemplateConfig::isWheelLotteryNewVersion($this->params['id'])) {
            ActivityLuckGamePlayNewValidation::make()->validators($this->params);
        } else {
            ActivityLuckGamePlayValidation::make()->validators($this->params);
        }
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page activityluckgameplay
     * @point 详情
     */
    public function infoAction()
    {
        $info = $this->service->info($this->params['id'] ?? 0);
        return $this->outputSuccess($info);
    }

    /**
     * @page activityluckgameplay
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'activityluckgameplay', model_id = 'id')
     */
    public function deleteAction()
    {
        $id = $this->params['id'] ?? 0;
        $c = $this->params['c'] ?? '';
        if (empty($id)) {
            return $this->outputError(-1, '活动ID错误');
        }
        if ($c == 'check') {
            $info = $this->service->info($id);
            return $this->outputSuccess([
                'is_confirm'   => 1,
                'confirm_text' => "你确定要删除【{$info['admin']}】创建的【{$info['title']}】吗？删除后将不可恢复"
            ]);
        }
        list($res, $msg) = $this->service->delete($id);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page activityluckgameplay
     * @point 发布
     * @logRecord(content = '发布', action = '1', model = 'activityluckgameplay', model_id = 'id')
     */
    public function publishAction()
    {
        return $this->outputSuccess($this->service->publish($this->params));
    }

    /**
     * @page activityluckgameplay
     * @point OA发布
     * @logRecord(content = 'OA发布', action = '1', model = 'activityluckgameplay', model_id = 'id')
     */
    public function oapubAction()
    {
        return $this->outputSuccess($this->service->publish($this->params, true));
    }

    /**
     * @page activityluckgameplay
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'activityluckgameplay', model_id = 'id')
     */
    public function copyAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, '活动ID错误');
        }
        [$res, $id] = $this->service->copy($id);
        if (!$res) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page activityluckgameplay
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->service->checkExport($this->params);
        ExportService::addTask($this->uid, 'activityluckgameplay.xlsx', [ActivityLuckGamePlayExport::class, 'export'], $params, '幸运玩法数据导出');
        ExportService::showHtml();
        return $this->outputSuccess();
    }

    /**
     * @page activityluckgameplay
     * @point 库存管理列表
     */
    public function getAwardListAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'getTab') {
            return $this->outputSuccess($this->service->getAwardTabList($this->params));
        }
        $list = $this->service->getAwardList($this->params);
        return $this->outputSuccess($list);
    }

    /**
     * @page activityluckgameplay
     * @point 库存管理编辑
     */
    public function awardModifyAction()
    {
        ActivityLuckGamePlayRewardValidation::make()->validators($this->params);
        $list = $this->service->awardModify($this->params);
        return $this->outputSuccess($list);
    }
}