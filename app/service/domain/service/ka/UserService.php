<?php

namespace Imee\Service\Domain\Service\Ka;

use Imee\Service\Domain\Service\Ka\Processes\User\SaveKaProcess;
use Imee\Service\Domain\Context\Ka\User\SaveKaContext;
use Imee\Service\Domain\Context\Ka\User\AllocationKfContext;
use Imee\Service\Domain\Service\Ka\Processes\User\AllocationKfProcess;
use Imee\Service\Domain\Context\Ka\User\BuildAlStatusContext;
use Imee\Service\Domain\Service\Ka\Processes\User\BuildAlStatusProcess;

class UserService
{
    //通过邀请码成为的ka用户
    public function saveKaByInvite($params)
    {
        $context = new SaveKaContext($params);
        $process = new SaveKaProcess($context);
        return $process->invite();
    }

    //通过邀请码成为的ka用户
    public function saveKaByExpLV($params)
    {
        $context = new SaveKaContext($params);
        $process = new SaveKaProcess($context);
        return $process->expLv();
    }

    //更新ka列表
    public function updateKa($params)
    {
        $context = new SaveKaContext($params);
        $process = new SaveKaProcess($context);
        return $process->update();
    }

    /**
     * 分配客服
     * @param  array  $params
     */
    public function allocationKf(array $params)
    {
        $context = new AllocationKfContext($params);
        $process = new AllocationKfProcess($context);
        return $process->handle();
    }

    /**
     * 建联
     * @param  array  $params
     * @return array
     */
    public function buildAlStatus(array $params)
    {
        $context = new BuildAlStatusContext($params);
        $process = new BuildAlStatusProcess($context);
        return $process->handle();
    }

    /**
     * 创建ka
     * @param  array  $params
     * @return array
     */
    public function create(array $params)
    {
        $context = new SaveKaContext($params);
        $process = new SaveKaProcess($context);
        return $process->create();
    }
}
