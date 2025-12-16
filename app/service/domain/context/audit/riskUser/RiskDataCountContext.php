<?php

namespace Imee\Service\Domain\Context\Audit\RiskUser;

use Imee\Service\Domain\Context\PageContext;

class RiskDataCountContext extends PageContext
{
	protected $sort = 'id';

	protected $dir = 'desc';

	/**
	 * 起始时间
	 * @var string
	 */
	protected $start;

	/**
	 * 结束时间
	 * @var string
	 */
	protected $end;
}