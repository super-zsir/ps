<?php

namespace Imee\Service\Domain\Service\Traits;

use Imee\Comp\Operate\Auth\Service\StaffService;
use Imee\Comp\Operate\Auth\Service\Context\Staff\BaseInfosContext as StaffBaseInfosContext;
use Imee\Service\Domain\Context\User\User\BaseInfosContext as UserBaseInfosContext;
use Imee\Service\Domain\Service\User\UserService;

trait UserInfoTrait
{
    private function getStaffBaseInfos($adminIds)
    {
        if (empty($adminIds)) {
            return [];
        }

        $staffBaseInfosContext = new StaffBaseInfosContext([
            'user_ids' => array_values($adminIds),
        ]);
        $service = new StaffService();
        return $service->getStaffBaseInfos($staffBaseInfosContext);
    }

    private function getUserInfoModel($uids)
    {
        $userBaseInfosContext = new UserBaseInfosContext([
            'user_ids' => array_values($uids),
        ]);
        $userService = new UserService();
        return $userService->getBaseModels($userBaseInfosContext);
    }

    private function getUserBigAreaModel($uids)
    {
        $userService = new UserService();
        return $userService->getUserBigAreaCode($uids);
    }

    /**
     * 根据UID 获取APP_ID，默认值0，取值后请注意判断，切勿用0入库
     * @param $uid
     * @return int|mixed
     */
    private function getAppIdByUid($uid)
    {
        if (!$uid) {
            return APP_ID;
        }
        $userInfo = $this->getUserInfoModel([$uid])->handle();
        if (isset($userInfo[$uid])) {
            return $userInfo[$uid]['app_id'];
        } else {
            return APP_ID;
        }
    }
}
