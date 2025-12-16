<?php

namespace Imee\Service\Domain\Service\Ka;

use Imee\Service\Domain\Context\Ka\Organization\ListGroupContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\ListGroupProcess;

use Imee\Service\Domain\Context\Ka\Organization\CreateGroupContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\CreateGroupProcess;

use Imee\Service\Domain\Context\Ka\Organization\ModifyGroupContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\ModifyGroupProcess;

use Imee\Service\Domain\Context\Ka\Organization\DeleteGroupContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\DeleteGroupProcess;

use Imee\Service\Domain\Context\Ka\Organization\ListContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\ListProcess;

use Imee\Service\Domain\Context\Ka\Organization\CreateContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\CreateProcess;

use Imee\Service\Domain\Context\Ka\Organization\ModifyContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\ModifyProcess;

use Imee\Service\Domain\Context\Ka\Organization\DeleteContext;
use Imee\Service\Domain\Service\Ka\Processes\Organization\DeleteProcess;

use Imee\Service\Domain\Service\Ka\Processes\Organization\GetKfHierarchyByOrgProcess;

class OrganizationService
{
    /**
     * 部门列表
     * @param $params
     * @return array
     */
    public function listGroup($params): array
    {
        $context = new ListGroupContext($params);
        $process = new ListGroupProcess($context);
        return $process->handle();
    }

    /**
     * 创建部门
     * @param $params
     * @return array
     */
    public function createGroup($params): array
    {
        $context = new CreateGroupContext($params);
        $process = new CreateGroupProcess($context);
        return $process->handle();
    }

    /**
     * 编辑部门
     * @param $params
     * @return array
     */
    public function modifyGroup($params): array
    {
        $context = new ModifyGroupContext($params);
        $process = new ModifyGroupProcess($context);
        return $process->handle();
    }

    /**
     * 删除部门
     * @param $params
     * @return array
     */
    public function deleteGroup($params): array
    {
        $context = new DeleteGroupContext($params);
        $process = new DeleteGroupProcess($context);
        return $process->handle();
    }

    /**
     * 获取用户列表
     * @param $params
     * @return array
     */
    public function getList($params): array
    {
        $context = new ListContext($params);
        $process = new ListProcess($context);
        return $process->handle();
    }

    /**
     * 分配用户
     * @param $params
     * @return array
     */
    public function create($params): array
    {
        $context = new CreateContext($params);
        $process = new CreateProcess($context);
        return $process->handle();
    }

    /**
     * 编辑用户
     * @param $params
     * @return array
     */
    public function modify($params): array
    {
        $context = new ModifyContext($params);
        $process = new ModifyProcess($context);
        return $process->handle();
    }

    /**
     * 删除用户
     * @param $params
     * @return array
     */
    public function delete($params): array
    {
        $context = new DeleteContext($params);
        $process = new DeleteProcess($context);
        return $process->handle();
    }

    public function getKfHierarchyByOrg(): array
    {
        $process = new GetKfHierarchyByOrgProcess();
        return $process->handle();
    }
}