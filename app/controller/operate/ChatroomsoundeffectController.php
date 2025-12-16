<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\ChatroomSoundEffectService;

class ChatroomsoundeffectController extends BaseController
{
    /**
     * @var ChatroomSoundEffectService $service
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ChatroomSoundEffectService();
    }

    /**
     * @page chatroomsoundeffect
     * @name 语音房音效管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  chatroomsoundeffect
     * @point 列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = trim($params['c'] ?? '');
        switch ($c) {
            case 'options':
                return $this->outputSuccess($this->service->getOptions());
            case 'info':
                return $this->outputSuccess($this->service->getInfo($this->params));
            default:
                $data = $this->service->getListAndTotal($this->params);
                return $this->outputSuccess($data['data'], ['total' => $data['total']]);
        }
    }

    /**
     * @page  chatroomsoundeffect
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'chatroomsoundeffect', model_id = 'id')
     */
    public function createAction()
    {
        list($flg, $rec) = $this->service->add($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  chatroomsoundeffect
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'chatroomsoundeffect', model_id = 'id')
     */
    public function modifyAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  chatroomsoundeffect
     * @point 更改状态
     * @logRecord(content = '更改状态', action = '1', model = 'chatroomsoundeffect', model_id = 'id')
     */
    public function changeStatusAction()
    {
        list($flg, $rec) = $this->service->modify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  chatroomsoundeffect
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'chatroomsoundeffect', model_id = 'id')
     */
    public function deleteAction()
    {
        list($flg, $rec) = $this->service->delete($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

}