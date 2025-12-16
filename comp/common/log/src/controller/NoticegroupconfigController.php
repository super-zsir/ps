<?php

namespace Imee\Controller\Log;

use Imee\Comp\Common\Log\Service\NoticeService;
use Imee\Controller\BaseController;

class NoticegroupconfigController extends BaseController
{
    public function onConstruct()
    {
        parent::onConstruct();
    }
    
    /**
     * @page noticegroupconfig
     * @name 通知群配置
     */
    public function mainAction()
    {
    }
    
    /**
     * @page noticegroupconfig
     * @point 列表
     */
    public function listAction()
    {
        $list = NoticeService::getGroupList($this->params);
        return $this->outputSuccess($list['data'], ['total' => $list['total']]);
    }
    
    /**
     * @page noticegroupconfig
     * @point 创建
     * @logRecord(content = '创建', action = '0', model = 'noticegroupconfig', model_id = 'id')
     */
    public function createAction()
    {
        $data = NoticeService::groupCreate($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page noticegroupconfig
     * @point 修改
     * @logRecord(content = '修改', action = '1', model = 'noticegroupconfig', model_id = 'id')
     */
    public function modifyAction()
    {
        $data = NoticeService::groupModify($this->params);
        return $this->outputSuccess($data);
    }
    
    /**
     * @page noticegroupconfig
     * @point 删除
     * @logRecord(content = '删除', action = '2', model = 'noticegroupconfig', model_id = 'id')
     */
    public function deleteAction()
    {
        $data = NoticeService::groupDelete($this->params);
        return $this->outputSuccess($data);
    }
}