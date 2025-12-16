<?php

namespace Imee\Controller\Ka;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Ka\OrganizationService;

class OrganizationController extends BaseController
{
    /**
     * @var OrganizationService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new OrganizationService();
    }

    /**
     * @page organization
     * @name KA用户管理-组织架构
     * @point 用户列表
     */
    public function listAction()
    {

        $res = $this->service->getList($this->params);
        $list  = $res['data'] ?? [];
        return $this->outputSuccess($list, ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page organization
     * @point 新增员工
     */
    public function createAction()
    {
        return $this->outputSuccess($this->service->create($this->params));
    }

    /**
     * @page organization
     * @point 编辑员工
     */
    public function modifyAction()
    {
        return $this->outputSuccess($this->service->modify($this->params));
    }

    /**
     * @page organization
     * @point 删除员工
     */
    public function deleteAction()
    {
        return $this->outputSuccess($this->service->delete($this->params));
    }

    /**
     * @page organization
     * @point 组别列表
     */
    public function listGroupAction()
    {
        return $this->outputSuccess($this->service->listGroup($this->params));
    }

    /**
     * @page organization
     * @point 创建部门
     */
    public function createGroupAction()
    {
        return $this->outputSuccess($this->service->createGroup($this->params));
    }

    /**
     * @page organization
     * @point 编辑部门
     */
    public function modifyGroupAction()
    {
        return $this->outputSuccess($this->service->modifyGroup($this->params));
    }

    /**
     * @page organization
     * @point 删除部门
     */
    public function deleteGroupAction()
    {
        return $this->outputSuccess($this->service->deleteGroup($this->params));
    }

}