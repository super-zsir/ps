<?php

namespace Imee\Service\Operate\Minicard;

use Imee\Comp\Common\Fixed\Utility;
use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsUserItemCard;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class MiniCardUserService
{

    public function getListAndTotal(array $params, int $page, int $limit): array
    {
        $condition = [];
        if (!empty($params['id'])) {
            $condition[] = ['id', '=', $params['id']];
        }
        if (!empty($params['item_card_id'])) {
            $itemCard = XsItemCard::findOne($params['item_card_id']);
            if (empty($itemCard) || $itemCard['type'] != $params['type']) {
                return ['data' => [], 'total' => 0];
            }
            $condition[] = ['item_card_id', '=', $params['item_card_id']];
        } else {
            $itemCards = XsItemCard::getListByWhere([
                ['type', '=', $params['type']]
            ], 'id');
            if (empty($itemCards)) {
                return ['data' => [], 'total' => 0];
            }
            $condition[] = ['item_card_id', 'in', array_column($itemCards, 'id')];
        }
        if (!empty($params['uid'])) {
            $condition[] = ['uid', '=', $params['uid']];
        }
        if (!empty($params['source'])) {
            $condition[] = ['source', '=', $params['source']];
        }
        if (!empty($params['give_source'])) {
            $condition[] = ['give_source', '=', $params['give_source']];
        }
        if (!empty($params['status'])) {
            $condition[] = ['status', '=', $params['status']];
        }
        if (!empty($params['give_source_uid'])) {
            $condition[] = ['give_source_uid', '=', $params['give_source_uid']];
        }
        if (!empty($params['dateline_sdate'])) {
            $dateline = strtotime($params['dateline_sdate']);
            $condition[] = ['create_time', '>=', $dateline];
        }
        if (!empty($params['dateline_edate'])) {
            $dateline = strtotime($params['dateline_edate']) + 86399;
            $condition[] = ['create_time', '<=', $dateline];
        }

        $data = XsUserItemCard::getListAndTotal($condition, '*', 'id desc', $page, $limit);
        if (empty($data['data'])) {
            return $data;
        }

        $cards = XsItemCard::getByIds(array_column($data['data'], 'item_card_id'));
        $users = XsUserProfile::getUserProfileBatch(array_column($data['data'], 'uid'));

        $logs = OperateLog::getFirstLogListMapping('homepagecarduser', array_column($data['data'], 'id'));

        $now = time();
        foreach ($data['data'] as &$item) {

            $card = $cards[$item['item_card_id']] ?? [];
            $item['icon_url'] = $card['icon'] ?? '';
            $item['name'] = $card['name'] ?? '';

            $item['uname'] = $users[$item['uid']]['name'] ?? '';

            $item['can_give'] = $item['can_give'] == 1 ? '可转赠' : '不可转赠';

            if ($item['expire_time'] > $now) {
                $leftTime = $item['expire_time'] - $now;
                $item['left_time'] = Utility::getTimeDurationYear($leftTime);
            }
            $item['expire_time'] = $item['expire_time'] > 0 ? Helper::now($item['expire_time']) : '';
            $item['period_end'] = $item['period_end'] > 0 ? Helper::now($item['period_end']) : '';
            
            $item['source'] = (string)$item['source'];
            $item['create_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';

            $log = $logs[$item['id']] ?? [];
            $item['invalid_operator'] = $log['operate_name'] ?? '';
            $item['invalid_time'] = ($log['created_time'] ?? 0) > 0 ? Helper::now($log['created_time']) : '';
        }

        return $data;
    }

    public function invalid(array $params): array
    {
        $id = $params['id'] ?? 0;
        if (!$id || $id < 1 || !($info = XsUserItemCard::findOne($id))) {
            throw new ApiException(ApiException::MSG_ERROR, '数据不存在');
        }

        if ($info['status'] == 3) {
            throw new ApiException(ApiException::MSG_ERROR, '该记录已失效');
        }

        $rpc = new PsRpc();
        $operater = Helper::getSystemUserInfo()['user_name'] ?? $params['admin_id'];

        $data = [
            'id'     => (int)$id,
            'oprater' => $operater,
        ];

        list($res, $_) = $rpc->call(PsRpc::API_MINI_CARD_INVALID, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return ['id' => $id, 'before_json' => ['status' => $info['status']], 'after_json' => ['status' => 3]];
        }

        throw new ApiException(ApiException::MSG_ERROR, '接口错误：' . $res['common']['msg']);
    }

    public function getStatusMap()
    {
        return StatusService::formatMap(XsUserItemCard::$statusMap);
    }
}