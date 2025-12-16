<?php

namespace Imee\Service\Operate\Certification;

use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsUserCertificationSign;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class CertificationInfoService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $fromTableName = XsUserCertificationSign::getTableName();
        $toTableName = XsCertificationSign::getTableName();

        $condition = $this->getConditions($params, $fromTableName, $toTableName);
        if (empty($condition)) {
            return ['data' => [], 'total' => 0];
        }
        $joinCondition = "{$fromTableName}.cer_id = {$toTableName}.id";

        $list = XsUserCertificationSign::getListJoinMaterials($condition, $joinCondition, "{$fromTableName}.id desc", $params['page'], $params['limit']);

        if (empty($list['data'])) {
            return $list;
        }

        $uids = array_column($list['data'], 'uid');
        $userList = XsUserProfile::getUserProfileBatch($uids);
        foreach ($list['data'] as &$item) {
            if ($item['expire_dateline'] > time()) {
                $item['status'] = 1;
            } else if ($item['expire_dateline'] < time()) {
                $item['status'] = 2;
            }
            $item['nickname']    = $userList[$item['uid']]['name'] ?? '';
            $item['create_dateline'] = empty($item['create_dateline']) ? '' : Helper::now($item['create_dateline']);
            if ($item['expire_dateline'] == -1) {
                $item['expire_dateline'] = '永久有效';
            } else if ($item['expire_dateline'] > 1) {
                $item['expire_dateline'] = Helper::now($item['expire_dateline']);
            } else {
                $item['expire_dateline'] = '';
            }
        }

        return $list;
    }

    public function modify(array $params): array
    {
        $info = XsUserCertificationSign::findOneByWhere([
            ['uid', '=', $params['uid'] ?? 0],
            ['cer_id', '=', $params['cer_id'] ?? 0],
        ]);

        if (empty($info)) {
            return [false, '当前修改的认证信息不存在或用户佩戴认证不可修改'];
        }

        if (isset($params['expire_dateline']) && !empty($params['expire_dateline'])) {
            $expireTime = strtotime($params['expire_dateline']);
            if ($expireTime > 4294967295) {
                return [false, '到期时间格式不正确，请检查后重试'];
            }
        } else {
            $expireTime = -1;
        }
        $data = [
            'id' => (int) $info['id'],
            'content' => $params['content'],
            'validity_value' => (int) $expireTime
        ];

        list($res, $msg) = $this->rpcService->updateCertificationSign($data);

        if (!$res) {
            return [false, $msg];
        }

        return [true, ['before_json' => $info, 'after_json' => $data]];
    }


    public function getConditions(array $params, string $fromTableName, string $toTableName): array
    {
        $conditions = [];

        if (isset($params['cer_id']) && !empty($params['cer_id'])) {
            $conditions[] = ["{$fromTableName}.cer_id", '=', $params['cer_id']];
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ["{$toTableName}.name", 'like', "%{$params['name']}%"];
        }

        if (isset($params['uid']) && !empty($params['uid'])) {
            $conditions[] = ["{$fromTableName}.uid", '=', $params['uid']];
        }

        if (empty($conditions)) {
            return $conditions;
        }

        if (isset($params['status']) && !empty($params['status'])) {
             if ($params['status'] == 1) {
                 $conditions[] = ["{$fromTableName}.expire_dateline", '>', time()];
             } else if ($params['status'] == 2) {
                 $conditions[] = ["{$fromTableName}.expire_dateline", '<', time()];
             }
        }

        return $conditions;
    }

}