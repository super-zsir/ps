<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\ActivityTaskGamePlayValidation;
use Imee\Export\Operate\Activity\ActivityTaskGamePlayExport;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayService;

class ActivitytaskgameplayController extends BaseController
{
    /**
     * @var ActivityTaskGamePlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityTaskGamePlayService();
    }
    
    /**
	 * @page activitytaskgameplay
	 * @name 单线任务&积分兑换
	 */
    public function mainAction()
    {
    }
    
    /**
	 * @page activitytaskgameplay
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
        $this->params['types'] = [BbcTemplateConfig::TYPE_TASK, BbcTemplateConfig::TYPE_GIFT_TASK, BbcTemplateConfig::TYPE_EXCHANGE];
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
    
    /**
	 * @page activitytaskgameplay
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'activitytaskgameplay', model_id = 'id')
	 */
    public function createAction()
    {
        $this->service->formatParams($this->params);
        ActivityTaskGamePlayValidation::make()->validators($this->params);
        [$res, $id] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }
    
    /**
	 * @page activitytaskgameplay
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'activitytaskgameplay', model_id = 'id')
	 */
    public function modifyAction()
    {
        $this->service->formatParams($this->params);
        if (!isset($this->params['id']) && empty($this->params['id'])) {
            return $this->outputError(-1, '数据错误');
        }
        ActivityTaskGamePlayValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page activitytaskgameplay
     * @point 详情
     */
    public function infoAction()
    {
        $info = $this->service->info($this->params['id'] ?? 0);
        return $this->outputSuccess($info);
    }

    /**
     * @page activitytaskgameplay
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'activitytaskgameplay', model_id = 'id')
     */
    public function deleteAction()
    {
        $c = $this->params['c'] ?? '';
        $id = $this->params['id'] ?? 0;
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
     * @page activitytaskgameplay
     * @point 发布
     * @logRecord(content = '发布', action = '1', model = 'activitytaskgameplay', model_id = 'id')
     */
    public function publishAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            return $this->outputSuccess($this->service->check($this->params));
        }
        return $this->outputSuccess($this->service->publish($this->params));
    }

    /**
     * @page activitytaskgameplay
     * @point OA发布
     * @logRecord(content = 'OA发布', action = '1', model = 'activitytaskgameplay', model_id = 'id')
     */
    public function oapubAction()
    {
        $this->service->pubAuthCheck($this->params);
        return $this->outputSuccess($this->service->publish($this->params, true));
    }

    /**
     * @page activitytaskgameplay
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'activitytaskgameplay', model_id = 'id')
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
     * @page activitytaskgameplay
     * @point 导出
     */
    public function exportAction()
    {
        $params = $this->service->checkExport($this->params);
        ExportService::addTask($this->uid, 'activitytaskgameplay.xlsx', [ActivityTaskGamePlayExport::class, 'export'], $params, '任务玩法数据导出');
        ExportService::showHtml();
        return $this->outputSuccess();
    }
}