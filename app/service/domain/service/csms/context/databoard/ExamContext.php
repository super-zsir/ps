<?php

namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

class ExamContext extends CommonKpiContext
{
    // 审核项
    protected $examItem = 2;

    // A类错审单个扣n分
    protected $examAless;

    // B类错审单个扣n分
    protected $examBless;

    // C类错审单个扣n分
    protected $examCless;

    // 无错审加n分
    protected $examGoodScore;
}
