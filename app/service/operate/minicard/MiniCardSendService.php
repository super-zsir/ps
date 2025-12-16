<?php

namespace Imee\Service\Operate\Minicard;

use Imee\Controller\Validation\Operate\Minicard\MiniCardSendValidation;
use Imee\Exception\ApiException;
use Imee\Models\Rpc\PsRpc;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsItemCardLog;
use Imee\Models\Xs\XsUserItemCard;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\StatusService;
use Exception;

class MiniCardSendService
{

    /** 
     * @var PsRpc $rpc 
     */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsRpc();
    }

    public function getListAndTotal(array $params, int $page, int $limit): array
    {
        $condition = [];
        if (!empty($params['item_card_id'])) {
            $itemCard = XsItemCard::findOne($params['item_card_id']);
            if (empty($itemCard) || $itemCard['type'] != $params['type']) {
                return ['data' => [], 'total' => 0];
            }
            $condition[] = ['item_card_id', '=', $params['item_card_id']];
        } else {
            $itemCards = XsItemCard::getListByWhere([['type', '=', $params['type']]], 'id');
            if (empty($itemCards)) {
                return ['data' => [], 'total' => 0];
            }
            $condition[] = ['item_card_id', 'IN', array_column($itemCards, 'id')];
        }
        if (!empty($params['uid'])) {
            $condition[] = ['uid', '=', $params['uid']];
        }

        // 转赠类型需要与资源ID或者UID同时筛选
        if (isset($params['can_give'])) {
            if (empty($condition)) {
                throw new ApiException(ApiException::MSG_ERROR, '转赠类型需要与资源ID或者UID同时筛选');
            }
            $userCards = XsUserItemCard::getListByWhere(array_merge($condition, [['can_give', '=', $params['can_give']]]), 'id');
            if (empty($userCards)) {
                return ['data' => [], 'total' => 0];
            }
            $condition[] = ['user_item_card_id', 'in', array_column($userCards, 'id')];
        }

        if (!empty($params['id'])) {
            $condition[] = ['id', '=', $params['id']];
        }
        
        if (!empty($params['give_source'])) {
            $condition[] = ['give_source', '=', $params['give_source']];
        }
        
        if (!empty($params['source'])) {
            $condition[] = ['source', '=', $params['source']];
        }
        if (!empty($params['dateline_sdate'])) {
            $dateline = strtotime($params['dateline_sdate']);
            $condition[] = ['create_time', '>=', $dateline];
        }
        if (!empty($params['dateline_edate'])) {
            $dateline = strtotime($params['dateline_edate']) + 86399;
            $condition[] = ['create_time', '<=', $dateline];
        }

        $data = XsItemCardLog::getListAndTotal($condition, '*', 'id desc', $page, $limit);
        if (empty($data['data'])) {
            return $data;
        }

        $cards = XsItemCard::getByIds(array_column($data['data'], 'item_card_id'));
        $users = XsUserProfile::getUserProfileBatch(array_column($data['data'], 'uid'));
        $userCards = XsUserItemCard::getByIds(array_column($data['data'], 'user_item_card_id'));

        foreach ($data['data'] as &$item) {

            $card = $cards[$item['item_card_id']] ?? [];
            $item['icon_url'] = $card['icon'] ?? '';
            $item['name'] = $card['name'] ?? '';

            $item['uname'] = $users[$item['uid']]['name'] ?? '';

            $userCard = $userCards[$item['user_item_card_id']] ?? [];
            $item['days'] = $userCard['days'] ?? '';
            $item['expire_time'] = $userCard['period_end'] ?? '';
            $item['can_give'] = $userCard['can_give'] ?? '';

            $item['source'] = (string)$item['source'];
            $item['create_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';
        }

        return $data;
    }

    public function create(array $params): array
    {
        $uids = trim(str_replace('，', ',', $params['uid']), ',');
        $uids = array_map('trim', explode(',', $uids));

        $filter = array_filter($uids, function ($id) {
            return is_numeric($id) && $id > 0;
        });

        if ($diff = array_diff($uids, $filter)) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID有误：' . implode(',', $diff));
        }

        $repeated = array_filter(array_count_values($uids), function ($count) {
            return $count > 1;
        });
        if ($repeated) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID有重复：' . implode(',', array_keys($repeated)));
        }

        if (count($uids) > 5000) {
            throw new ApiException(ApiException::MSG_ERROR, '一次最多发放5000个用户');
        }

        foreach (array_chunk($uids, 1000) as $uidArr) {
            $users = XsUserProfile::findByIds($uidArr, 'uid');
            if ($diff = array_diff($uidArr, array_column($users, 'uid'))) {
                throw new ApiException(ApiException::MSG_ERROR, 'UID不存在：' . implode(',', $diff));
            }
            usleep(10 * 1000);
        }

        $log = [
            'resource_id' => (int)$params['resource_id'],
            'days'        => (int)$params['days'],
            'period_days' => (int)$params['period_days'],
            'num'         => (int)$params['num'],
            'can_give'    => (int)$params['can_give'],
            'remark'      => $params['remark'],
        ];
        $logs = [];
        foreach ($uids as $uid) {
            $log['uid'] = (int)$uid;
            $logs[] = $log;
        }

        $operater = Helper::getSystemUserInfo()['user_name'] ?? $params['admin_id'];

        $data = [
            'log'            => $logs,
            'oprater'        => $operater,
            'item_card_type' => $params['type'],
        ];
        list($res, $_) = $this->rpc->call(PsRpc::API_MINI_CARD_SEND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        throw new ApiException(ApiException::MSG_ERROR, '接口错误：' . $res['common']['msg']);


        /*$total = 0;
        $errors = [];

        foreach (array_chunk($logs, 5000) as $log) {
            if (!$log) {
                continue;
            }
            $data = [
                'log'     => $log,
                'oprater' => $operater,
            ];

            list($res, $_) = $rpc->call(PsRpc::API_MINI_CARD_SEND, ['json' => $data]);
            if (isset($res['common']) && $res['common']['err_code'] == 0) {
                $total += count($log);
            } else {
                $errors[] = $res['common']['msg'];
            }

            usleep(100 * 1000);
        }

        if ($errors) {
            array_unshift($errors, '成功下发条数：' . $total);
            return [false, $errors];
        }

        return [true, ''];*/
    }

    public function import(array $data, int $type): array
    {
        foreach ($data as $k => &$item) {
            $item = Helper::trimParams($item);
            $line = $k + 1;

            try {
                MiniCardSendValidation::make()->validators($item);
                if (!is_numeric($item['uid']) || $item['uid'] < 1) {
                    throw new ApiException(ApiException::MSG_ERROR, "第{$line}行UID有误：" . $item['uid']);
                }
            } catch (ApiException $e) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$line}行：" . $e->getMsg());
            } catch (Exception $e) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$line}行：" . $e->getMessage());
            }

            foreach ($item as $field => $value) {
                if ($field == 'remark') {
                    $item[$field] = trim($value);
                } else {
                    $item[$field] = (int)$value;
                }
            }
        }

        $uids = array_unique(array_column($data, 'uid'));
        foreach (array_chunk($uids, 1000) as $uidArr) {
            $users = XsUserProfile::findByIds($uidArr, 'uid');
            if ($diff = array_diff($uidArr, array_column($users, 'uid'))) {
                throw new ApiException(ApiException::MSG_ERROR, 'UID不存在：' . implode(',', $diff));
            }
            usleep(10 * 1000);
        }
        $resourceIds = array_unique(array_column($data, 'resource_id'));
        $res = XsItemCard::findByIds($resourceIds, 'id, type');
        if ($diff = array_diff($resourceIds, array_column($res, 'id'))) {
            throw new ApiException(ApiException::MSG_ERROR, '资源ID不存在：' . implode(',', $diff));
        }
        $errorTypeIds = [];
        foreach($res as $val) {
            if ($val['type'] != $type) {
                $errorTypeIds[] = $val['id'];
            }
        }
        if ($errorTypeIds) {
            throw new ApiException(ApiException::MSG_ERROR, '资源类型错误，错误资源ID：' . implode(',', $errorTypeIds));
        }
        $data = [
            'log'            => array_values($data),
            'oprater'        => Helper::getSystemUserInfo()['user_name'] ?? '未登录',
            'item_card_type' => $type,
        ];
        list($res, $_) =  $this->rpc->call(PsRpc::API_MINI_CARD_SEND, ['json' => $data]);
        if (isset($res['common']) && $res['common']['err_code'] == 0) {
            return [true, ''];
        }
        throw new ApiException(ApiException::MSG_ERROR, $res['common']['msg'] ?? ('接口返回错误：' . json_encode($res)));
    }

    public function getSourceMap(): array
    {
        $map = XsItemCardLog::$sourceMap;
        return StatusService::formatMap($map);
    }

    public function getCardMap($value = XsItemCard::TYPE_MINI, $format = 'label,value'): array
    {
        $map = XsItemCard::getMap($value);
        return StatusService::formatMap($map, $format);
    }

    public function getGiveMap(): array
    {
        $map = [
            '0' => '不可转赠',
            '1' => '可转赠',
        ];
        return StatusService::formatMap($map);
    }
}