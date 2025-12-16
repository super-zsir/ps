<?php

namespace Imee\Service\Domain\Context\Vipsend;

use Imee\Service\Domain\Context\BaseContext;

class CreateContext extends BaseContext
{
    /**
     * @var int
     */
    public $vipLevel;

    /**
     * @var int
     */
    public $vipDay;

    /**
     * @var string
     */
    public $uids;

    /**
     * @var string
     */
    public $remark;

    /**
     * @var int
     */
    public $type;

    /**
     * @var int
     */
    public $sendNum;

    /**
     * @var int
     */
    public $adminId;

}
