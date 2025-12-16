<?php

namespace Imee\Service\Operate\Play\Roomrocket;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class RoomRocketConfigService
{
    private $prefix = 'lv_';

    public function getList(array $params): array
    {
        [$res, $msg, $data] = (new PsService())->roomRocketConfigList($params);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data = array_column($data, null, 'big_area_id');
        $bigareaIds = array_column($data, 'bigarea_id');
        $logs = BmsOperateLog::getFirstLogList('roomrocketconfig', $bigareaIds);
        $bigareas = XsBigarea::getAreaList();
        foreach ($bigareas as &$val) {
            $val['bigarea_id'] = $val['id'];
            $lvs = $data[$val['id']]['lvs'] ?? [];
            $val['config_num'] = count($lvs);
            if ($lvs) {
                foreach ($lvs as $k => $lv) {
                    $val[$this->prefix . $k] = $lv;
                }
            }
            $val['effective_time'] = isset($data[$val['id']]['effective_time']) ? Helper::now($data[$val['id']]['effective_time']) : '';
            $val['admin_name'] = $logs[$val['id']]['operate_name'] ?? '';
            $val['dateline'] = isset($logs[$val['id']]['created_time']) ? Helper::now($logs[$val['id']]['created_time']) : '';
        }
        return ['list' => $bigareas, 'total' => count($bigareas)];
    }

    public function modify(int $id, int $status)
    {
        $info = XsBigarea::findOne($id);
        if (empty($info)) {
            return [false, '当前大区不存在'];
        }
        $update = [
            'big_area_id' => (int) $id,
            'on' => (bool) $status,
        ];
        list($res, $msg) = (new PsService())->setRoomRocketSwitch($update);
        if (!$res) {
            return [false, $msg];
        }
        $beforeJson = [
            'id' => $id,
            'switch' => $info['boom_rocket_switch']
        ];

        return [true, ['before_json' => $beforeJson, 'after_json' => [
            'id' => $id,
            'switch' => $status
        ]]];
    }

    public function editBigAreaConfig(array $params)
    {
        $minThreshold = 0;
        $backLv = 0;
        $i = 1;
        if (!isset($params['configs']) || empty($params['configs'])) {
            throw new ApiException(ApiException::MSG_ERROR, '等级配置不可全部删除');
        }
        foreach ($params['configs'] as &$config) {
            if (isset($config['lv'])) {
                if ($backLv != $config['lv'] - 1) {
                    throw new ApiException(ApiException::MSG_ERROR, '等级只能逐层个删除');
                }
                $backLv = $config['lv'];
            } else {
                $config['lv'] = $i;
            }
            $i++;
            if (($config['threshold'] < $minThreshold && !empty($minThreshold)) || intval($config['threshold']) <= 0) {
                throw new ApiException(ApiException::MSG_ERROR, '等级积分必须是逐步增多并且必须为正整数');
            }
            $config['show_banner'] = (bool) $config['show_banner'];
            $config['top_in_feed'] = (bool) $config['top_in_feed'];
            $config['top_in_feed_duration'] = intval($config['top_in_feed_duration']) * 60;
            $minThreshold = $config['threshold'];
        }
        $data = [
            'big_area_id' => (int) $params['bigarea_id'],
            'version' => $params['version'],
            'configs' => $params['configs']
        ];
        list($res, $msg) = (new PsService())->setRoomRocketBigAreaConfig($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    public function editAwardConfig(array $params)
    {
        $awardConfig = [];
        foreach ($params as $key => $param) {
            if (preg_match('/lv_\d/', $key)) {
                $lv = str_replace($this->prefix, '', $key);
                foreach ($param as $award) {
                    if ($award['num'] <= 0) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励份数为大于0的正整数');
                    }
                    if (in_array($award['type'], [3, 4]) && $award['day_num'] <= 0) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励天数为大于0的正整数');
                    }
                    if (in_array($award['type'], [1]) && $award['per_num'] <= 0) {
                        throw new ApiException(ApiException::MSG_ERROR, '奖励数量为大于0的正整数');
                    }
                    $awardConfig[] = [
                        'lv' => (int) $lv,
                        'target_type' => (int) $award['target_type'],
                        'type' => (int) $award['type'],
                        'num' => (int) $award['num'],
                        'award_id' => (int) ($award['award_id'] ?? 0),
                        'per_num' => $award['type'] == 1 ? $award['per_num'] : $award['day_num'] ?? 0
                    ];
                }
            }
        }

        $data = [
            'big_area_id' => (int) $params['bigarea_id'],
            'configs' => $awardConfig
        ];

        list($res, $msg) = (new PsService())->setRoomRocketAwardConfig($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];

    }

    public function getOptions()
    {
        $service = new StatusService();
        $area = $service->getFamilyBigArea(null, 'label,value');
        $commodity = $service->getCommodityMap(null, 'label,value');
        $medal = $service->getMedalMap(null, 'label,value');
        $roomBackground = $service->getRoomBackgroundMap(null, 'label,value');

        return compact('area', 'commodity', 'medal', 'roomBackground');
    }

    public function info(int $id)
    {
        list($res, $msg, $data) = (new PsService())->getRoomRocketBigAreaInfo($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        foreach ($data['configs'] as &$config) {
            $config['show_banner'] = (string) intval($config['show_banner']);
            $config['top_in_feed'] = (string) intval($config['top_in_feed']);
            $config['top_in_feed_duration'] = $config['top_in_feed_duration'] / 60;
        }

        return ['bigarea_id' => $id, 'configs' => $data['configs'], 'version' => $data['version']];
    }

    public function awardInfo(int $id)
    {
        list($res, $msg, $configs) = (new PsService())->getRoomRocketBigAreaAwardInfo($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        $data = [
            'bigarea_id' => (string) $id,
        ];
        foreach ($configs as &$config) {
            $key = $this->prefix . $config['lv'];
            if ($config['type'] == '3' || $config['type'] == '4') {
                $config['day_num'] = $config['per_num'];
                $config['per_num'] = 0;
            }
            $config['target_type'] = (string) $config['target_type'];
            $config['award_id'] = (string) $config['award_id'];
            $config['type'] = (string) $config['type'];
            $data[$key][] = $config;
        }
        return $data;
    }
}