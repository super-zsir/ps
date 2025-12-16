<?php

namespace Imee\Service\Operate\Lighting;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsNameIdLightingLog;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class NameIdLightingLogService
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
        $groupId = intval(array_get($params, 'group_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $startTime = trim(array_get($params, 'create_time_sdate', ''));
        $endTime = trim(array_get($params, 'create_time_edate', ''));
        $canGive = array_get($params, 'can_give');
        $source = array_get($params, 'source');
        $giveSource = array_get($params, 'give_source');

        $startTime = $startTime ? strtotime($startTime) : 0;
        $endTime = $endTime ? strtotime($endTime . ' 23:59:59') : 0;

        $query = ['page' => ['page_index' => $page, 'page_size' => $limit]];
        $id && $query['id'] = $id;
        $groupId && $query['group_id'] = $groupId;
        $uid && $query['uid'] = $uid;
        $startTime && $query['start_time'] = $startTime;
        $endTime && $query['end_time'] = $endTime;
        is_numeric($canGive) && $query['can_give'] = intval($canGive);
        is_numeric($source) && $query['source'] = intval($source);
        if (is_numeric($giveSource) && $giveSource >= 0) {
            $query['give_source'] = intval($giveSource);
        }


        list($flg, $msg, $data) = $this->rpc->listNameIdLightingLog($query);
        if (!$flg) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($data['data'] as &$rec) {
            $_createTime = array_get($rec, 'create_time', 0);
            $_periodEnd = array_get($rec, 'period_end', 0);
            $_resourceName = $rec['resource_name'] ? @json_decode($rec['resource_name'], true) : [];


            $rec['create_time'] = $_createTime ? date('Y-m-d H:i:s', $_createTime) : '';
            $rec['period_end'] = $_periodEnd ? date('Y-m-d H:i:s', $_periodEnd) : '';
            $rec['resource_icon_url'] = $rec['resource_icon'] ? Helper::getHeadUrl($rec['resource_icon']) : '';
            $rec['resource_name_show'] = XsNameIdLightingGroup::formatName($_resourceName);
        }
        return $data;
    }

    public function add(array $params, $adminId = ''): array
    {
        $uid = str_replace('，', ',', trim($params['uid'] ?? ''));
        $uidArr = explode(',', $uid);
        $data = [];
        foreach ($uidArr as $_uid) {
            $params['uid'] = $_uid;
            $data[] = $this->validateAndFormatData($params);
        }

        list($flg, $msg, $data) = $this->rpc->addNameIdLighting([
            'log'     => $data,
            'oprater' => Helper::getAdminName($adminId)
        ]);

        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    public function addBatch(array $params, $adminId = ''): array
    {
        $data = [];
        foreach ($params as $rec) {
            $data[] = $this->validateAndFormatData($rec);
        }
        list($flg, $msg, $data) = $this->rpc->addNameIdLighting([
            'log'     => $data,
            'oprater' => Helper::getAdminName($adminId)
        ]);

        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    private function validateAndFormatData($params): array
    {
        $groupId = intval(array_get($params, 'group_id', 0));
        $uid = intval(array_get($params, 'uid', 0));
        $days = intval(array_get($params, 'days', 0));
        $periodDays = intval(array_get($params, 'period_days', 0));
        $num = intval(array_get($params, 'num', 0));
        $canGive = intval(array_get($params, 'can_give', 0));
        $remark = trim(array_get($params, 'remark', ''));

        $user = XsUserProfile::findOne($uid);
        if (empty($user)) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('用户[%d]不存在', $uid));
        }

        return [
            'group_id'    => $groupId,
            'uid'         => $uid,
            'days'        => $days,
            'period_days' => $periodDays,
            'num'         => $num,
            'can_give'    => $canGive,
            'remark'      => $remark,
        ];
    }


    public static function getGroupIdMap($value = null, string $format = '')
    {
        $lists = XsNameIdLightingGroup::getListByWhere([], 'id, name');
        $lists = array_column($lists, 'name', 'id');

        $map = [];
        foreach ($lists as $k => $list) {
            $_tmp = $list ? @json_decode($list, true) : [];
            $_name = isset($_tmp['zh_cn']) ? $_tmp['zh_cn'] : '';
            empty($_name) && $_name = isset($_tmp['zh_tw']) ? $_tmp['zh_tw'] : '';
            empty($_name) && $_name = isset($_tmp['en']) ? $_tmp['en'] : '';
            if (empty($_name) && empty($_tmp)) {
                $_name = $list;
            }
            $map[$k] = sprintf('%d - %s', $k, $_name);
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getSourceMap($value = null, string $format = '')
    {
        $map = XsNameIdLightingLog::$sourceMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }
}