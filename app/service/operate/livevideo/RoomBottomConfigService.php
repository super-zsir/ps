<?php

namespace Imee\Service\Operate\Livevideo;

use Imee\Models\Xs\XsRoomBottomConfig;
use Imee\Models\Xs\XsRoomBottomConfigLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class RoomBottomConfigService
{
    /**
     * @var PsService $rpc
     */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $now = time();
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $rid = intval(array_get($params, 'rid', 0));
        $bigAreaId = intval(array_get($params, 'big_area_id', 0));
        $state = array_get($params, 'state');

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $rid && $query[] = ['rid', '=', $rid];
        $bigAreaId && $query[] = ['big_area_id', '=', $bigAreaId];

        if (is_numeric($state)) {
            if ($state) {
                $query[] = ['status', '=', $state];
                $query[] = ['end_time', '>', $now];
            } else {
                $idList = XsRoomBottomConfig::find([
                    'conditions' => "status = 0 OR end_time <= {$now}",
                    "columns"    => "id",
                    "order"      => "id DESC",
                    "limit"      => 10000,
                ])->toArray();
                $idList = array_column($idList, 'id');

                if (empty($idList)) {
                    return ['data' => [], 'total' => 0];
                } else {
                    $query[] = ['id', 'IN', $idList];
                }
            }
        }


        $data = XsRoomBottomConfig::getListAndTotal($query, '*', 'end_time desc, id desc', $page, $limit);

        $allUid = array_column($data['data'], 'uid');
        $allUid = XsUserProfile::getListByWhere([['uid', 'IN', $allUid]], 'uid, name');
        $allUid = array_column($allUid, 'name', 'uid');

        foreach ($data['data'] as &$rec) {
            $_start = array_get($rec, 'start_time', 0);
            $_end = array_get($rec, 'end_time', 0);
            $_uid = array_get($rec, 'uid', 0);

            $rec['end_min'] = $_end > $now ? ceil(($_end - $now) / 60) : 0;

            $rec['state'] = 0;
            if ($rec['end_min'] && $rec['status'] == XsRoomBottomConfig::STATUS_NORMAL) {
                $rec['state'] = 1;
            }

            $rec['start_time'] = $_start ? date('Y-m-d H:i:s', $_start) : '';
            $rec['end_time'] = $_end ? date('Y-m-d H:i:s', $_end) : '';
            $rec['uname'] = $allUid[$_uid] ?? '';
        }
        return $data;
    }

    public function getLogListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));

        $query = [];
        $id && $query[] = ['config_id', '=', $id];


        $data = XsRoomBottomConfigLog::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $_dateline = array_get($rec, 'dateline', 0);
            $config = XsRoomBottomConfig::findOne(intval($rec['config_id']));
            $rec['dateline'] = $_dateline ? date('Y-m-d H:i:s', $_dateline) : '';
            $rec['rid'] = $config['rid'] ?? '';
        }
        return $data;
    }


    public function config(array $params): array
    {
        $id = intval(array_get($params, 'id', 0));
        $rid = intval(array_get($params, 'rid', 0));
        $adminId = array_get($params, 'admin_id', 0);
        $operator = Helper::getAdminName($adminId);

        $data = [
            'uid'      => (int)array_get($params, 'uid', 0),
            'property' => (int)array_get($params, 'property', 1),
            'op_type'  => (int)array_get($params, 'op_type', 2),
            'minutes'  => (int)array_get($params, 'minutes', 0),
            'reason'   => trim(array_get($params, 'reason', '')),
            'op_uid'   => $adminId,
            'operator' => $operator,
        ];

        $rid && $data['rid'] = $rid;

        list($flg, $rec) = $this->rpc->roomBottomConfig($data);
//        $flg && XsRoomBottomConfigLog::add([
//            'config_id' => $id,
//            'op_type'  => $data['op_type'],
//            'minutes'  => $data['minutes'],
//            'reason'   => $data['reason'],
//            'op_uid'   => $adminId,
//            'operator' => $operator,
//            'dateline' => time(),
//        ]);
        return [$flg, $rec];
    }

    public static function getPropertyMap($value = null, string $format = '')
    {
        $map = XsRoomBottomConfig::$propertyMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getStatusMap($value = null, string $format = '')
    {
        $map = XsRoomBottomConfig::$statusMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}