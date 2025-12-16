<?php

namespace Imee\Service\Domain\Service\User;

use Imee\Service\Domain\Service\User\Processes\User\BaseInfoModelsProcess;
use Imee\Service\Domain\Context\User\User\BaseInfosContext;
use Imee\Service\Domain\Service\User\Processes\User\BaseInfosProcess;
use Imee\Service\Domain\Service\User\Processes\User\BigAreaInfosProcess;

/**
 * 用户服务
 */
class UserService
{
    public function getBaseModels(BaseInfosContext $context)
    {
        $process = new BaseInfoModelsProcess($context);
        return $process;
    }

    public function getBaseInfos($params)
    {
        $context = new BaseInfosContext($params);
        $process = new BaseInfosProcess($context);
        return $process->handle();
    }

    public function getUserBigAreaCode($userIds)
	{
		$params = [
			'user_ids' => array_values($userIds),
		];
		$context = new BaseInfosContext($params);
		$process = new BigAreaInfosProcess($context);
		return $process->handle();
	}
}
