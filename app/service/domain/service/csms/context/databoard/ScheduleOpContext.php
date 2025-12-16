<?php


namespace Imee\Service\Domain\Service\Csms\Context\Databoard;

use Imee\Service\Domain\Context\BaseContext;

class ScheduleOpContext extends BaseContext
{
    /**
     * @var int 考勤次数
     */
    protected $turnoutNum;
    /**
     * @var int 对应人员
     */
    protected $admin;

    /**
     * @var int a类违规
     */
    protected $aNum;

    /**
     * @var int b类违规
     */
    protected $bNum;

    /**
     * @var int c类违规
     */
    protected $cNum;

    /**
     * @var int 操作人
     */
    protected $opUid;

    /**
     * @var int id
     */
    protected $id;

    protected $dateline;
}
