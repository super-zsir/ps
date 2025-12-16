<?php

namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

class KpiModifyContext extends CommonKpiContext
{
    // 审核项
    protected $examItem = 3;

    // 平均审核时效高于目标n秒
    protected $auditTimeMore;

    // 平均审核时效每高于目标,扣n分
    protected $auditTimeDescScore;

    // 平均审核时效低于目标n秒
    protected $auditTimeLess;

    // 平均审核时效每低于目标,加n分
    protected $auditTimeAddScore;

    // 平均审核时效最多加N分
    protected $auditTimeMax;
}
