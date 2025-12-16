<?php

namespace Imee\Service\Operate\Background\Custombackground;

use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class CustomBgcCardSwitchService
{
    public function getList(array $params): array
    {
        $list = (new PsService())->customBgcCardSwitchList([]);
        if (empty($list['data'])) {
            return [];
        }
        $bigareaIds = array_column($list['data'], 'big_area_id');
        $logs = BmsOperateLog::getFirstLogList('custombgccardswitch', $bigareaIds);
        foreach ($list['data'] as &$v) {
            $v['bigarea_id'] = (string)$v['big_area_id'];
            $v['switch'] = (string)$v['customize_bg_switch'];
            $v['admin_name'] = $logs[$v['big_area_id']]['operate_name'] ?? '-';
            $v['dateline'] = isset($logs[$v['big_area_id']]['created_time']) ? Helper::now($logs[$v['big_area_id']]['created_time']) : '';
        }
        return $list;
    }

    public function modify(int $bigAreaId, int $status)
    {
        $update = [
            'big_area_id' => (int) $bigAreaId,
            'customize_bg_switch' => (int) $status,
        ];
        list($res, $msg) = (new PsService())->saveCustomBgcCardSwitch($update);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }
}