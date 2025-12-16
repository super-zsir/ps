<?php

namespace Imee\Service\Operate\Play\Redpacket;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsBigareaSwitch;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class RedPacketService extends BaseService
{
    public function getBigAreaList(array $params): array
    {
        $list = XsBigarea::getListAndTotal([], 'id,name,red_packet_config', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('redpacketbigarea', $bigAreaIds);
        foreach ($list['data'] as &$v) {
            $v['name'] = XsBigarea::getBigAreaCnName($v['name']);
            $config = json_decode($v['red_packet_config'], true);
            $v['switch'] = (string)($config['switch'] ?? 0);
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }
        return $list;
    }

    public function getCountList(array $params): array
    {
        $conditions = $this->getNumConditions($params);
        $list = XsBigarea::getListAndTotal($conditions, 'id, red_packet_config', 1, 15);
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('redpacketnum', $bigAreaIds);
        foreach ($list['data'] as &$v) {
            $v['big_area'] = (string) $v['id'];
            $diamond = json_decode($v['red_packet_config'], true)['diamond'] ?? [];
            $this->formatConfig($v, $diamond);
            $v['update_name'] = $logs[$v['id']]['operate_name'] ?? '-';
            $v['update_time'] = isset($logs[$v['id']]['created_time']) ? Helper::now($logs[$v['id']]['created_time']) : '';
        }

        return $list;
    }

    public function modifyBigAreaSwitch(array $params)
    {
        $data = [
            'big_area_id' => $params['id'],
            'config' => [
                'switch' => $params['switch'],
            ],
            'op' => self::OP_SWITCH,
            'modify_type' => self::MODIFY_TYPE_ORDINARY
        ];

        $this->modify($data);
    }

    public function modifyNumber(array $params)
    {
        $diamond = $this->formatData($params);
        $data = [
            'big_area_id' => $params['id'],
            'config' => [
                'diamond' => $diamond,
            ],
            'op' => self::OP_CONFIG,
            'modify_type' => self::MODIFY_TYPE_ORDINARY
        ];
        $this->modify($data);
    }
}