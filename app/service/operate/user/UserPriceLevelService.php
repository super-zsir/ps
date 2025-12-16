<?php

namespace Imee\Service\Operate\User;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateHistory;

class UserPriceLevelService
{
    use SingletonTrait;

    public function getList(array $uids): array
    {
        $data = [];
        $data['page'] = ['page_index' => 1, 'page_size' => count($uids)];
        $data['query'] = ['app_id' => APP_ID, 'uid' => $uids];

        list($data, $code) = (new PsRpc())->call(PsRpc::API_PRICE_LEVEL, ['json' => $data]);
        if (empty($data['exp'])) {
            return [];
        }
        return array_column($data['exp'], 'lv', 'uid');
    }

    public function update(array $data, int $op): array
    {
        if (empty($data['uid']) || !is_numeric($data['price_level'])) return [false, '数据错误'];
        if ($data['price_level'] < 0) return [false, '等级不能为负'];

        if ($data['price_level'] > 50 && !XsUserProfile::hasFiftyUserLevelPurview()) {
            return [false, '权限不足，无法进行该用户等级修改'];
        }

        $prev = $this->getList([$data['uid']]);
        if (($prev[$data['uid']] ?? 0) > 50 && !XsUserProfile::hasFiftyUserLevelPurview()) {
            return [false, '权限不足，无法进行该用户等级修改'];
        }

        $data = ['app_id' => APP_ID, 'uid' => intval($data['uid']), 'lv' => intval($data['price_level'])];
        list($res, $code) = (new PsRpc())->call(PsRpc::API_UPDATE_PRICE_LEVEL, ['json' => $data]);
        if ($res['success']) {
            BmsOperateHistory::insertLog(BmsOperateHistory::PRICE_LEVEL, $data['uid'], ['level' => $data['lv'], 'prev' => $prev[$data['uid']] ?? 0], $op);
        }
        return [$res['success'], $res['msg'] ?? ''];
    }

    public function getHistory(int $uid, array $params = []): array
    {
        if ($uid < 1) return ['data' => [], 'total' => 0];
        $data = BmsOperateHistory::getHistoryBySid(BmsOperateHistory::PRICE_LEVEL, $uid, $params);

        if (empty($data['data'])) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($data['data'] as &$v) {
            if (!empty($v['content'])) $v = array_merge($v, json_decode($v['content'], true));
        }
        return $data;
    }
}
