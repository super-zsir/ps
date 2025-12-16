<?php

namespace Imee\Service\Operate\Lighting;

use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsNameIdLightingStyle;
use Imee\Models\Xs\XsUserNameIdLighting;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class UserNameIdLightingService
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
        $giveSource = array_get($params, 'give_source');
        $status = array_get($params, 'status', '');

        $startTime = $startTime ? strtotime($startTime) : 0;
        $endTime = $endTime ? strtotime($endTime . ' 23:59:59') : 0;

        $query = ['page' => ['page_index' => $page, 'page_size' => $limit]];
        $id && $query['id'] = $id;
        $groupId && $query['group_id'] = $groupId;
        $uid && $query['uid'] = $uid;
        $startTime && $query['start_time'] = $startTime;
        $endTime && $query['end_time'] = $endTime;
        is_numeric($status) && $query['status'] = intval($status);
        if (is_numeric($giveSource) && $giveSource >= 0) {
            $query['give_source'] = intval($giveSource);
        }

        list($flg, $msg, $data) = $this->rpc->listUserNameIdLighting($query);
        if (!$flg) {
            return ['data' => [], 'total' => 0];
        }

        foreach ($data['data'] as &$rec) {
            $_dateline = $rec['dateline'] ?? 0;
            $_dressTime = $rec['dress_time'] ?? 0;
            $_periodEnd = $rec['period_end'] ?? 0;
            $_resourceName = $rec['resource_name'] ? @json_decode($rec['resource_name'], true) : [];
            $_styleConfig = array_get($rec, 'style_config', []);


            $rec['dateline'] = $_dateline ? date('Y-m-d H:i:s', $_dateline) : '';
            $rec['dress_time'] = $_dressTime ? date('Y-m-d H:i:s', $_dressTime) : '';
            $rec['period_end'] = $_periodEnd ? date('Y-m-d H:i:s', $_periodEnd) : '';
            $rec['resource_name_show'] = XsNameIdLightingGroup::formatName($_resourceName);
            $rec['resource_name_format'] = $_resourceName;

            $rec['resource_icon_show'] = $rec['resource_icon'] ? Helper::getHeadUrl($rec['resource_icon']) : '';

            if (is_array($_styleConfig)) {
                $_styleName = $_styleConfig['style_name'] ? @json_decode($_styleConfig['style_name'], true) : [];
                $_styleConfig['style_name_format'] = $_styleName;
                $_styleConfig['style_name_show'] = XsNameIdLightingGroup::formatName($_styleName);

                $_styleConfig['multi_color_direction'] = (string)$_styleConfig['multi_color_direction'];
                $_styleConfig['multi_color_style'] = (string)$_styleConfig['multi_color_style'];
                $_styleConfig['lighting_direction'] = (string)$_styleConfig['lighting_direction'];
                $_styleConfig['lighting_color'] = $this->getColorEnum($_styleConfig['lighting_color']);
            }

            $rec['style_config'] = $_styleConfig;

            $rec['style_id_show'] = [
                'dataType' => "object",
                'title' => "预览",
                'value' => $rec['style_id'],
                'type' => 'manMadeModal',
                'modal_id' => 'user_name_lighting_preview',
                'params' => $_styleConfig,
            ];
        }
        return $data;
    }

    public function invalid($params): array
    {
        $id = intval($params['id'] ?? 0);
        $adminId = intval($params['admin_id'] ?? 0);
        $oprater = Helper::getAdminName($adminId);

        $beforeJson = XsUserNameIdLighting::findOne($id);
        list($flg, $msg, $data) = $this->rpc->invalidUserNameIdLighting([
            'id'      => $id,
            'oprater' => $oprater,
        ]);
        $afterJson = $flg ? XsUserNameIdLighting::findOne($id, true) : [];

        return [$flg, $flg ? ['uid' => $beforeJson['uid'] ?? 0, 'before_json' => $beforeJson, 'after_json' => $afterJson] : $msg];
    }

    public static function getCanGiveMap($value = null, string $format = '')
    {
        $map = XsUserNameIdLighting::$canGiveMap;
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
        $map = XsUserNameIdLighting::$sourceMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getSceneMap($value = null, string $format = '')
    {
        $map = XsUserNameIdLighting::$sceneMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getDressStatusMap($value = null, string $format = '')
    {
        $map = XsUserNameIdLighting::$dressStatusMap;
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
        $map = XsUserNameIdLighting::$statusMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    private function getColorEnum($data)
    {
        if (count($data) === 3) {
            foreach (XsNameIdLightingStyle::$color as $k => $v) {
                if (in_array($data[0], $v) && in_array($data[1], $v) && in_array($data[2], $v)) {
                    return (string)$k;
                }
            }
        }
        return $data;
    }

}