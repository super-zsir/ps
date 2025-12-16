<?php

namespace Imee\Service\Domain\Service\User\Processes\User;

use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Domain\Context\User\User\BaseInfosContext;

/**
 * 获取用户基本信息
 */
class BaseInfosProcess
{
    use UserInfoTrait;
    private $context;
    public function __construct(BaseInfosContext $context)
    {
        $this->context = $context;
    }

    private function buildWhere()
    {
        $where = [
            'condition' => [],
            'bind' => [],
        ];
        if (!empty($this->context->userIds)) {
            $where['condition'][] = 'uid in({user_ids:array})';
            $where['bind']['user_ids'] = $this->context->userIds;
        }


        return $where;
    }

    public function handle()
    {
        $where = $this->buildWhere();
        $returnData = [];

        if (empty($where['condition'])) {
            return $returnData;
        }
    
        $userModels = XsUserProfile::find([
            'conditions' => implode(' and ', $where['condition']),
            'bind' => $where['bind'],
        ]);
        foreach ($userModels as $userModel) {
            $tmp = [
                'uid' => $userModel->uid,
                'sex' => $userModel->sex,
                'app_id' => $userModel->app_id,
                'icon' => $userModel->icon,
                'name' => $userModel->name,
                'pay_room_money' => $userModel->pay_room_money,
                'deleted' => $userModel->deleted,
                'title' => $userModel->title,
                'dateline' => $userModel->dateline,
                'role' => $userModel->role,
                'online_status' => $userModel->online_status,
                'online_dateline' => $userModel->online_dateline,
            ];
           
            $returnData[$userModel->uid] = $tmp;
        }
        
        return $returnData;
    }
}
