<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;

class ShowOriginUidSwitch
{
    protected $models = [
        8 => 'showoriginuidswitch',
        9 => 'guestrelationjumpswitch',
    ];

    public function getList(int $type): array
    {
        $list = XsBigarea::getListAndTotal([], 'id, show_origin_uid_switch, guest_relation_jump_switch');
        $bigareaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList($this->models[$type], $bigareaIds);
        foreach ($list['data'] as &$item) {
            $item['bigarea_id'] = $item['id'];
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
        }
        return $list;
    }

    public function edit(int $switch, int $id, int $type): array
    {
        $data = [];
        if ($type == XsBigarea::SHOW_ORIGIN_UID_SWITCH) {
            $data['show_origin_uid_switch'] = $switch;
        } else if ($type == XsBigarea::GUEST_RELATION_JUMP_SWITCH) {
            $data['guest_relation_jump_switch'] = $switch;
        } else {
            // todo 待扩展
        }
        return XsBigarea::edit($id, $data);
    }
}