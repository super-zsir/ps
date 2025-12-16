<?php


namespace Imee\Service\Domain\Context\Pretty\Style;

use Imee\Service\Domain\Context\PageContext;

/**
 * 靓号类型
 */
class ListContext extends PageContext
{
    protected $sort = 'id';

    protected $dir = 'desc';

    /**
     * @var int id
     */
    protected $id;

    /**
     * @var string 类型名称
     */
    protected $name;

    /**
     * @var int disabled
     */
    protected $disabled;
}
