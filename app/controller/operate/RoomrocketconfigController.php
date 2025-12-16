<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\Play\Roomrocket\RoomRocketConfigService;

class RoomrocketconfigController extends BaseController
{
    /**
     * @var RoomRocketConfigService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new RoomRocketConfigService();
    }

    /**
     * @page roomrocketconfig
     * @name 玩法管理-爆火箭玩法配置-爆火箭任务配置
     */
    public function mainAction()
    {
    }

    /**
     * @page roomrocketconfig
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'options') {
            $data = $this->service->getOptions();
            return $this->outputSuccess($data);
        }
        $res = $this->service->getList($this->params);
        return $this->outputSuccess($res['list'] ?? [], ['total' => $res['total'] ?? 0]);
    }


    /**
     * @page roomrocketconfig
     * @point 修改大区配置
     * @logRecord(content = '修改大区配置', action = '1', model = 'roomrocketconfig', model_id = 'id')
     */
    public function modifyBigAreaConfigAction()
    {
        if (!isset($this->params['bigarea_id']) || empty($this->params['bigarea_id'])) {
            return $this->outputError(-1, '大区必填');
        }
        if (isset($this->params['configs']) && count($this->params['configs']) > 4) {
            return $this->outputError(-1, '等级配置最多为4个');
        }
        $data = $this->service->editBigAreaConfig($this->params);

        return $this->outputSuccess($data);
    }

    /**
     * @page roomrocketconfig
     * @point 修改奖励配置
     * @logRecord(content = '修改奖励配置', action = '1', model = 'roomrocketconfig', model_id = 'id')
     */
    public function awardAction()
    {
        if (!isset($this->params['bigarea_id']) || empty($this->params['bigarea_id'])) {
            return $this->outputError(-1, '大区必填');
        }
        $data = $this->service->editAwardConfig($this->params);

        return $this->outputSuccess($data);
    }

    /**
     * @page roomrocketconfig
     * @point 大区等级配置详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id) || $id < 0) {
            return $this->outputError(-1, 'ID有误');
        }

        $data = $this->service->info($id);

        return $this->outputSuccess($data);
    }

    /**
     * @page roomrocketconfig
     * @point 奖励配置详情
     */
    public function awardInfoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id) || $id < 0) {
            return $this->outputError(-1, 'ID有误');
        }

        $data = $this->service->awardInfo($id);

        return $this->outputSuccess($data);
    }
}