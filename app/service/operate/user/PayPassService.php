<?php

namespace Imee\Service\Operate\User;

use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserEmail;
use Imee\Models\Xs\XsUserPayPassword;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class PayPassService
{
    // 重置功能标识
    const RESET_PAY_PASS = 1;
    const RESET_PAY_PASS_NUM = 2;
    const RESET_EMAIL = 3;

    // 重置接口集合
    public $interfaceMap = [
        self::RESET_PAY_PASS => PsRpc::API_RESET_PAY_PASS,
        self::RESET_PAY_PASS_NUM => PsRpc::API_RESET_PAY_PASS_NUM,
        self::RESET_EMAIL => PsRpc::API_RESET_EMAIL,
    ];

    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);
        if (empty($conditions)) {
            return [];
        }
        $joinConditions = $this->getJoinCondition();
        $columns = $this->getColumns();

        $list = XsUserProfile::getListJoinTable($conditions, $joinConditions, $columns, 'u.uid desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if ($list['data']) {
            $uids = array_column($list['data'], 'uid');
            $logs = BmsOperateLog::getFirstLogList('paypass', $uids);
            foreach ($list['data'] as &$item) {
                $item['pay_pass_state'] = isset($item['p_uid']) ? XsUserPayPassword::STATE_YES : XsUserPayPassword::STATE_NO;
                $times = [
                    (int) filter_var($logs[$item['uid']]['created_time'] ?? 0, FILTER_VALIDATE_INT),
                    (int) filter_var($item['e_dateline'], FILTER_VALIDATE_INT),
                    (int) filter_var($item['p_dateline'], FILTER_VALIDATE_INT),
                ];
                max($times) && $item['update_time'] = Helper::now(max($times));
            }
        }

        return $list;
    }

    public function getCount(array $params): int
    {
        $joinConditions = $this->getJoinCondition();
        $conditions = $this->getConditions($params);
        return XsUserProfile::getJoinCount($conditions, $joinConditions);
    }

    public function reset($uids, $type)
    {
        [$res, $msg] = (new PsService())->userPayPassAndEmailReset($uids, $this->interfaceMap[$type]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function modifyEmail(int $uid, string $email)
    {
        [$res, $msg] = (new PsService())->userEmailModify($uid, $email);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getJoinCondition(): array
    {
        $joinConditions = [
            [
                'class' => XsUserEmail::class,
                'condition' => 'e.uid = u.uid',
                'table' => 'e'
            ],
            [
                'class' => XsUserPayPassword::class,
                'condition' => 'p.uid = u.uid',
                'table' => 'p'
            ],
            [
                'class' => XsUserBigarea::class,
                'condition' => 'b.uid = u.uid',
                'table' => 'b'
            ]
        ];
        return $joinConditions;

    }

    public function getConditions(array $params): array
    {
        $conditions = [];
        if (isset($params['uid']) && !empty($params['uid'])) {
            $uids = Helper::formatIdString($params['uid']);
            $conditions[] = ['u.uid', 'in', $uids];
        }
        if (isset($params['bigarea_id']) && !empty($params['bigarea_id'])) {
            $conditions[] = ['b.bigarea_id', '=', $params['bigarea_id']];
        }
        if (isset($params['email']) && !empty($params['email'])) {
            $conditions[] = ['e.email', 'like', "%{$params['email']}%"];
        }
        if (isset($params['pay_pass_state']) && !empty($params['pay_pass_state'])) {
            if ($params['pay_pass_state'] == XsUserPayPassword::STATE_YES) {
                $conditions[] = ['p.uid', 'IS NOT NULL', ''];
            } else {
                $conditions[] = ['p.uid', 'IS NULL', ''];
            }
        }
        return $conditions;
    }

    public function getColumns()
    {
        return [
            'u.uid as uid',
            'u.name as nickname',
            'b.bigarea_id as bigarea_id',
            'e.email as email',
            'e.dateline as e_dateline',
            'p.uid as p_uid',
            'p.update_dateline as p_dateline'
        ];

    }
}