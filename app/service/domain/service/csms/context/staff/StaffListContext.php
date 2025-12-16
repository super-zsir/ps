<?php


namespace Imee\Service\Domain\Service\Csms\Context\Staff;


use Imee\Service\Domain\Context\PageContext;

class StaffListContext extends PageContext
{


	protected $sort = 'id';

	protected $dir = 'desc';

	protected $userId;

}