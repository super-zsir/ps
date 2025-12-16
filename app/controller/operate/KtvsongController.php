<?php

namespace Imee\Controller\Operate;

use Imee\Controller\BaseController;
use Imee\Service\Operate\KtvSongService;

class KtvsongController extends BaseController
{
    /**
     * @var KtvSongService
     */
    private $service;

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->service = new KtvSongService();
    }

    /**
     * @page  ktvsong
     * @name 运营系统-歌曲管理
     */
    public function mainAction()
    {
    }

    /**
     * @page  ktvsong
     * @point 列表
     */
    public function listAction()
    {
        $res = $this->service->getListAndTotal($this->params);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page  ktvsong
     * @point 编辑
     * @logRecord(content = '状态', action = '1', model = 'ktvsong', model_id = 'id')
     */
    public function modifyAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg] = $this->service->edit($this->params);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }

    /**
     * @page  ktvsong
     * @point 状态
     * @logRecord(content = '状态', action = '3', model = 'ktvsong', model_id = 'id')
     */
    public function statusAction()
    {
        if (empty($this->params['id'])) {
            return $this->outputError(-1, 'ID必传');
        }
        [$res, $msg] = $this->service->status($this->params['id'], $this->params['status']);
        if (!$res) {
            return $this->outputError(-1, $msg);
        }
        return $this->outputSuccess([]);
    }
}