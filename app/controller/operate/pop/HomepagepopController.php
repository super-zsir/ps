<?php

namespace Imee\Controller\Operate\Pop;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\Pop\HomePagePopValidation;
use Imee\Service\Operate\Pop\HomePagePopService;

class HomepagepopController extends BaseController
{
    /**
     * @var HomePagePopService $service
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new HomePagePopService();
    }

    /**
     * @page homepagepop
     * @name 首页弹窗
     */
    public function mainAction()
    {
    }

    /**
     * @page homepagepop
     * @point 列表
     */
    public function listAction()
    {
        $list = $this->service->getList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page homepagepop
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'homepagepop', model_id = 'id')
     */
    public function modifyAction()
    {
        $this->params['id'] = empty($this->params['id']) ? 0 : $this->params['id'];
        HomePagePopValidation::make()->validators($this->params);
        $id = $this->service->modify($this->params);
        return $this->outputSuccess(['after_json' => $this->params, 'id' => $id]);
    }

    /**
     * @page homepagepop
     * @point 禁用
     * @logRecord(content = '禁用', action = '1', model = 'homepagepop', model_id = 'id')
     */
    public function disableAction()
    {
        $id = $this->params['id'] ?? 0;
        if (empty($id)) {
            return $this->outputError(-1, 'ID错误');
        }
        $this->service->disable($id);
        return $this->outputSuccess(['after_json' => $this->params]);
    }
}