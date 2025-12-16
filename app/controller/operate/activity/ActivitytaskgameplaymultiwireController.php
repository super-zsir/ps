<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\ActivityTaskGamePlayMultiwireValidation;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayMultiwireService;

class ActivitytaskgameplaymultiwireController extends BaseController
{
    /**
     * @var ActivityTaskGamePlayMultiwireService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityTaskGamePlayMultiwireService();
    }

    /**
	 * @page activitytaskgameplaymultiwire
	 * @name 多线独立任务
	 */
    public function mainAction()
    {
    }

    /**
	 * @page activitytaskgameplaymultiwire
	 * @point 列表
	 */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            $options = $this->service->getOptions();
            return $this->outputSuccess($options);
        } else if ($c == 'reward') {
            $options = $this->service->getRewardOptions($this->params['id'] ?? 0);
            return $this->outputSuccess($options);
        }
        $this->params['types'] = [BbcTemplateConfig::TYPE_MULTI_TASK];
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
	 * @page activitytaskgameplaymultiwire
	 * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'activitytaskgameplaymultiwire', model_id = 'id')
	 */
    public function createAction()
    {
        $this->service->formatParams($this->params);
        ActivityTaskGamePlayMultiwireValidation::make()->validators($this->params);
        $data = $this->service->create($this->params);
        return $this->outputSuccess($data);
    }

    /**
	 * @page activitytaskgameplaymultiwire
	 * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'activitytaskgameplaymultiwire', model_id = 'id')
	 */
    public function modifyAction()
    {
        $this->service->formatParams($this->params);
        if (!isset($this->params['id']) && empty($this->params['id'])) {
            return $this->outputError(-1, '数据错误');
        }
        ActivityTaskGamePlayMultiwireValidation::make()->validators($this->params);
        $data = $this->service->modify($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page activitytaskgameplaymultiwire
     * @point 详情
     */
    public function infoAction()
    {
        $info = $this->service->info($this->params['id'] ?? 0);
        return $this->outputSuccess($info);
    }

    /**
     * @page activitytaskgameplaymultiwire
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'activitytaskgameplaymultiwire', model_id = 'id')
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
     * @page activitytaskgameplaymultiwire
     * @point 配置任务
     * @logRecord(content = '配置任务', action = '1', model = 'activitytaskgameplaymultiwire', model_id = 'id')
     */
    public function taskAction()
    {
        $this->params['tab_list'] = @json_decode($this->params['tab_list'] ?? [], true);
        $data = $this->service->setTask($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page activitytaskgameplaymultiwire
     * @point 获取配置任务
     */
    public function getTaskAction()
    {
        return $this->outputSuccess($this->service->getTask($this->params));
    }

    /**
     * @page activitytaskgameplaymultiwire
     * @point 发布
     * @logRecord(content = '发布', action = '1', model = 'activitytaskgameplaymultiwire', model_id = 'id')
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
     * @page activitytaskgameplaymultiwire
     * @point OA发布
     * @logRecord(content = 'OA发布', action = '1', model = 'activitytaskgameplaymultiwire', model_id = 'id')
     */
    public function oapubAction()
    {
        $this->service->pubAuthCheck($this->params);
        return $this->outputSuccess($this->service->publish($this->params, true));
    }

    /**
     * @page activitytaskgameplaymultiwire
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'activitytaskgameplaymultiwire', model_id = 'id')
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
}