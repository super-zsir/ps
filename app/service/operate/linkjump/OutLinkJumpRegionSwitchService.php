<?php

namespace Imee\Service\Operate\Linkjump;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class OutLinkJumpRegionSwitchService
{
    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, url_link_switch', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('outlinkjumpregionswitch', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_name'] = XsBigarea::getBigAreaCnName($v['name']);
            $v['switch'] = $v['url_link_switch'];
            $v['display_switch'] = XsBigarea::$displayInviteGiftSwitch[$v['switch']] ?? '';
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
            'switch' => $status,
        ];
        list($res, $msg) = (new PsService())->setUrlLinkSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'url_link_switch' => $info['url_link_switch']
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'url_link_switch' => $status
        ]]];
    }
}
