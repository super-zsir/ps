<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class WelcomebigareaService
{
    public function getInviteGiftSwitch()
    {
        $format = [];
        foreach (XsBigarea::$displayInviteGiftSwitch as $k => $v) {
            $tmp['label'] = $v;
            $tmp['value'] = $k;
            $format[] = $tmp;
        }
        return $format;
    }

    public function getList(array $params): array
    {
        $res = XsBigarea::getListAndTotal([], 'id, name, invite_gift_switch', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigareaIds = array_column($res['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('welcomebigarea', $bigareaIds);
        foreach ($res['data'] as &$v) {
            $v['bigarea_name'] = XsBigarea::getBigAreaCnName($v['name']);
            $v['invite_gift_switch'] = $v['invite_gift_switch'];
            $v['display_invite_gift_switch'] = isset(XsBigarea::$displayInviteGiftSwitch[$v['invite_gift_switch']]) ?
                XsBigarea::$displayInviteGiftSwitch[$v['invite_gift_switch']] : '';
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
            'on' => $status ? true : false,
        ];
        list($res, $msg) = (new PsService())->modifyWelcombigarea($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'invite_gift_switch' => $info['invite_gift_switch']
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'invite_gift_switch' => $status
        ]]];
    }
}
