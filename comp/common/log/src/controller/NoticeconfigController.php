<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\NoticeService;
use Imee\Controller\BaseController;

class NoticeconfigController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page noticeconfig
     * @name 通知配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page noticeconfig
     * @point 列表
     */
    public function listAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'getModule') {
            $name = trim($this->params['str'] ?? '');
            return $this->outputSuccess(NoticeService::getModuleMap($name));
        } else if ($c == 'getAction') {
            $mid = intval($this->params['mid'] ?? 0);
            return $this->outputSuccess(NoticeService::getActionMap($mid));
        } else if ($c == 'getOptions') {
            return $this->outputSuccess(NoticeService::getOptions());
        }
        $list = NoticeService::getNoticeList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
     * @page noticeconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'noticeconfig', model_id = 'id')
     */
    public function createAction()
    {
        $data = NoticeService::noticeCreate($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page noticeconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'noticeconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = NoticeService::noticeModify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page noticeconfig
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'noticeconfig', model_id = 'id')
     */
    public function deleteAction()
    {
        $data = NoticeService::noticeDelete($this->params);
        return $this->outputSuccess($data);
    }

    /**
     * @page noticeconfig
     * @point 详情
     */
    public function infoAction()
    {
        $data = NoticeService::noticeDetail(intval($this->params['id'] ?? 0));
        return $this->outputSuccess($data);
    }
}