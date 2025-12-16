<?php

namespace Imee\Service\Domain\Context\Cs\Statistics\FirstChatRecord;

use Imee\Service\Domain\Context\PageContext;

/**
 * 首次会话记录列表上下文
 */
class ListContext extends PageContext
{
	protected $sort = 'id';

	protected $dir = 'desc';

	/**
	 * @var start
	 */
	protected $start;

	/**
	 * @var end
	 */
	protected $end;

	/**
	 * @var from_big_area
	 */
	protected $fromBigArea;

	/**
	 * @var from_sex
	 */
	protected $fromSex;

	/**
	 * @var to_sex
	 */
	protected $toSex;

	/**
	 * @var is_reply
	 */
	protected $isReply;
}
