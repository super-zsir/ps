<?php

namespace Imee\Service\Operate\Livesticker;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RegionSwitchService
{
    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, custom_sticker_switch', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('customstickerregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['id'];
            $v['switch'] = (string)$v['custom_sticker_switch'];
            $v['admin_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $res;
    }

    public function modify(int $id, int $status)
    {
        $info = XsBigarea::findOne($id);
        if (empty($info)) {
            return [false, '当前大区不存在'];
        }
        $update = [
            'big_area_id' => $id,
            'switch' => $status
        ];
        list($res, $msg) = (new PsService())->setCustomStickerSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'big_area_id' => $id,
            'switch' => json_decode($info['custom_sticker_switch'], true)['switch'] ?? 0
        ];

        return [true, ['before_json' => $beforeJson, $update]];
    }
}