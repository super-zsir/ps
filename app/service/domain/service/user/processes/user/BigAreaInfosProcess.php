<?php

namespace Imee\Service\Domain\Service\User\Processes\User;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Domain\Context\User\User\BaseInfosContext;

/**
 * 获取用户大区
 */
class BigAreaInfosProcess
{
    use UserInfoTrait;
    private $context;
    public function __construct(BaseInfosContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $returnData = [];

        if (empty($this->context->userIds)) {
            return $returnData;
        }

		$userBigArea = XsUserBigarea::getUserBigareas($this->context->userIds);
        if (empty($userBigArea)) return $returnData;

        $bigAreaCode = XsBigarea::getAllBigAreaCode();
        foreach ($userBigArea as $uid => $areaId) {
			$returnData[$uid] = $bigAreaCode[$areaId] ?? '-';
		}

        return $returnData;
    }
}
