<?php


namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

use Imee\Service\Domain\Context\PageContext;

class DailyContext extends PageContext
{
    // 员工id
    protected $admin;

    // 审核项
    protected $auditItem;

    // 操作项
    protected $actionItem;

    // 审核阶段
    protected $verifyType;

    // 员工姓名
    protected $staffName;

    // 维度
    protected $groupBy;

    // start
    protected $start = 0;

    // app
    protected $appId;

    // 是否机审
    protected $isMachine;

    // 开始时间
    protected $datelineStart;

    // 结束时间
    protected $datelineEnd;

    protected $area;

    protected $type;

    protected $refresh;
}
