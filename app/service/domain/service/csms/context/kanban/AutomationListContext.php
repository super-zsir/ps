<?php

namespace Imee\Service\Domain\Service\Csms\Context\Kanban;

use Imee\Service\Domain\Context\PageContext;

/**
 * 自动化数据管理
 * Class AutomationListContext
 * @package Imee\Service\Domain\Service\Csms\Context\Kanban
 */
class AutomationListContext extends PageContext
{

    /**
     * @var string $format 格式 date  week
     */
    public $format;

    public $type;

    public $beginTime;

    public $endTime;

}