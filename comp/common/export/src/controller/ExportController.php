<?php

namespace Imee\Controller\Common\Export;

use Imee\Comp\Common\Export\Service\CmsUserService;
use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;

class ExportController extends BaseController
{
    /**
     * @name 导出组件-导出列表
     * @page export
     * @point 导出列表
     */
    public function listAction()
    {
        $where = [];
        $where['op_uid'] = $this->request->get('op_uid', 'int!', 0);
        $page = $this->request->get('page', 'int!', 1);
        $limit = $this->request->get('limit', 'int!', 20);

        if (!CmsUserService::isSuper($this->uid)) {
            $where['op_uid'] = $this->uid;
        }

        $data = ExportService::getList($where, $limit, $page);
        $data['is_super'] = CmsUserService::isSuper($this->uid);
        return $this->outputSuccess($data);
    }

    /**
     * @page export
     * @point 用户列表
     */
    public function userListAction()
    {
        $page = $this->request->get('page', 'int!', 1);
        $limit = $this->request->get('limit', 'int!', 20);
        $where = [];
        if (!CmsUserService::isSuper($this->uid)) {
            $where['user_id'] = $this->uid;
        }
        $data = CmsUserService::getUserList($where, $limit, $page);
        return $this->outputSuccess($data);
    }

    /**
     * @page export
     * @point 导出
     */
    public function exportAction()
    {
        $params = [];
        $params['op_uid'] = $this->request->get('op_uid', 'int!', 0);

        if (!CmsUserService::isSuper($this->uid)) {
            $params['op_uid'] = $this->uid;
        }

        ExportService::addTask($this->uid, 'export.csv', [ExportService::class, 'export'], $params, '导出任务');
        return $this->outputSuccess();
    }
}