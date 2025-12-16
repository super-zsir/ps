<?php

namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

class AuditContext extends CommonKpiContext
{
    // 审核项
    protected $examItem = 1;

    // 考核量每少n个
    protected $auditLessNum;

    // 考核量每少，扣n分
    protected $auditLessScore;

    // 考核量每增加N个
    protected $auditAddNum;

    // 考核量每增加，加n分
    protected $auditAddScore;

    // 考核量最多加n分
    protected $auditMaxAdd;
}
