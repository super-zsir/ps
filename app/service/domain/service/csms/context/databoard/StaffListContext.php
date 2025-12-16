<?php

namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

use Imee\Service\Domain\Context\BaseContext;

class StaffListContext extends BaseContext
{

    // 日期
    protected $date;

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

    // 排
    protected $sort;

    // 序
    protected $dir;

    // start
    protected $start = 0;

    // limit
    protected $limit;

    protected $page;

    protected $area;

    protected $type;

    protected $refresh;

    protected $endDate;

    protected $startDate;
}
