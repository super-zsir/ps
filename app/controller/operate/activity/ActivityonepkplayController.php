<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Fixed\Csv;
use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Activity\ActivityOnePkPlayValidation;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Operate\Activity\ActivityOnePkPlayService;

class ActivityonepkplayController extends BaseController
{
    /**
     * @var ActivityOnePkPlayService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityOnePkPlayService();
    }

    /**
     * @page activityonepkplay
     * @name 1v1 PK玩法
     */
    public function mainAction()
    {
    }

    /**
     * @page activityonepkplay
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'enum') {
            $enum = $this->service->enum();
            return $this->outputSuccess($enum);
        }
        $this->params['types'] = [BbcTemplateConfig::TYPE_ONE_PK];
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page activityonepkplay
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'activityonepkplay', model_id = 'id')
     */
    public function createAction()
    {
        ActivityOnePkPlayValidation::make()->validators($this->params);
        [$res, $id] = $this->service->add($this->params);
        if (!$res) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page activityonepkplay
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'activityonepkplay', model_id = 'id')
     */
    public function modifyAction()
    {
        if (!isset($params['id']) && empty($this->params['id'])) {
            return $this->outputError(-1, '数据错误');
        }
        ActivityOnePkPlayValidation::make()->validators($this->params);
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess(['after_json' => $this->params]);
    }

    /**
     * @page activityonepkplay
     * @point 详情
     */
    public function infoAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, '数据错误');
        }

        $info = $this->service->info($id);

        return $this->outputSuccess($info);
    }

    /**
     * @page activityonepkplay
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'activityonepkplay', model_id = 'id')
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
     * @page activityonepkplay
     * @point 发布
     * @logRecord(content = '修改', action = '1', model = 'activityonepkplay', model_id = 'id')
     */
    public function releaseAction()
    {
        return $this->outputSuccess($this->service->publish($this->params));
    }

    /**
     * @page activityonepkplay
     * @point 更新
     * @logRecord(content = '修改', action = '1', model = 'activityonepkplay', model_id = 'id')
     */
    public function upAction()
    {
        return $this->outputError(-1, '该功能已下线');
//        return $this->outputSuccess($this->service->up($this->params));
    }

    /**
     * @page activityonepkplay
     * @point 复制
     * @logRecord(content = '复制', action = '0', model = 'activityonepkplay', model_id = 'id')
     */
    public function copyAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, '活动ID错误');
        }
        [$res, $id] = $this->service->copy($id, true);
        if (!$res) {
            return $this->outputError(-1, $id);
        }
        return $this->outputSuccess(['id' => $id, 'after_json' => $this->params]);
    }

    /**
     * @page activityonepkplay
     * @point 导出
     */
    public function exportAction()
    {
        $file = $this->service->export($this->params);

        (new Csv())->downLoadCsv($file, 'onePkDataDownload');
    }
}
