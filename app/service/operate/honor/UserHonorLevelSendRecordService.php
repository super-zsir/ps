<?php

namespace Imee\Service\Operate\Honor;

use Imee\Models\Xs\XsUserHonorLevelSendRecord;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class UserHonorLevelSendRecordService
{
    /** @var PsService $rpc */
    protected $rpc;


    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = (int)array_get($params, 'limit', 15);
        $page = (int)array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $configId = intval(array_get($params, 'config_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $startTime = trim(array_get($params, 'create_time_sdate', ''));
        $endTime = trim(array_get($params, 'create_time_edate', ''));
        $status = array_get($params, 'status', '');

        $startTime = $startTime ? strtotime($startTime) : 0;
        $endTime = $endTime ? strtotime($endTime . ' 23:59:59') : 0;

        $query = ['page' => $page, 'limit' => $limit];
        $id && $query['id'] = $id;
        $configId && $query['config_id'] = $configId;
        $uid && $query['uid'] = $uid;
        $startTime && $query['start_time'] = $startTime;
        $endTime && $query['end_time'] = $endTime;
        is_numeric($status) && $query['status'] = intval($status);

        list($flg, $msg, $data) = $this->rpc->userHonorLevelManageList($query);
        if (!$flg) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($data['data'] as &$rec) {
            $_create = $rec['create_time'] ?? 0;
            $_disabled = $rec['disable_time'] ?? 0;
            $_styleConfig = $rec['style_config'] ?? [];

            $rec['level_show'] = sprintf("%d - %d", $rec['min_level'] ?? '', $rec['max_level'] ?? '');
            $rec['create_time'] = $_create ? date('Y-m-d H:i:s', $_create) : '';
            $rec['disable_time'] = $_disabled ? date('Y-m-d H:i:s', $_disabled) : '';
            $rec['level_icon_show'] = Helper::getHeadUrl($_styleConfig['level_icon'] ?? '');
            $rec['style_icon_show'] = Helper::getHeadUrl($_styleConfig['style_icon'] ?? '');
            $rec['font_color_show'] = implode(',', $_styleConfig['font_color'] ?? []);
            $rec['send_source'] = isset(XsUserHonorLevelSendRecord::$sourceMap[$rec['send_source']]) ? XsUserHonorLevelSendRecord::$sourceMap[$rec['send_source']] : '';
            $rec['model_id'] = $rec['id'];
        }
        return $data;
    }

    public function invalid($params): array
    {
        $id = intval($params['id'] ?? 0);
        $adminId = intval($params['admin_id'] ?? 0);
        $operator = Helper::getAdminName($adminId);

        $beforeJson = XsUserHonorLevelSendRecord::findOne($id);
        list($flg, $msg, $data) = $this->rpc->userHonorLevelDisable([
            'id'       => $id,
            'operator' => $operator,
        ]);
        $afterJson = $flg ? XsUserHonorLevelSendRecord::findOne($id, true) : [];
        return [$flg, $flg ? ['uid' => $beforeJson['uid'] ?? 0, 'before_json' => $beforeJson, 'after_json' => $afterJson] : $msg];
    }

    public static function getStatusMap($value = null, string $format = '')
    {
        $map = XsUserHonorLevelSendRecord::$statusMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}