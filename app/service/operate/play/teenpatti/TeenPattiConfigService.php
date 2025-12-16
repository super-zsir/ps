<?php

namespace Imee\Service\Operate\Play\Teenpatti;

use Imee\Exception\ApiException;
use Imee\Helper\Traits\ResponseTrait;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstTeenPattiConfig;
use Imee\Service\Helper;

class TeenPattiConfigService
{
    use ResponseTrait;

    public function getList(array $params, int $config): array
    {
        $list = XsstTeenPattiConfig::getListAndTotal([], '*', 'id asc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return [];
        }
        if ($config == 1) {
            $data = $this->formatGearList($list);
        } else {
            $data = $this->formatScaleList($list);
        }
        return $data;
    }

    public function editGearConfig(array $params): array
    {
        $config = XsstTeenPattiConfig::findOne($params['id']);
        if (empty($config)) {
            return [false, '当前配置不存在'];
        }
        $gearConfig = $this->setGearConfig($params);
        if (empty($gearConfig)) {
            return [false, '档位必填1项'];
        }

        [$res, $msg] = XsstTeenPattiConfig::edit($params['id'], [
            'gear_config' => json_encode($gearConfig)
        ]);

        if (!$res) {
            return [false, $msg];
        }

        return [true, [
            'type' => 0,
            'before_json' => [
                'gear_config' => json_decode($config['gear_config'], true),
                ],
            'after_json' => [
                'gear_config' => $gearConfig,
            ]
        ]];
    }

    public function editScaleConfig(array $params): array
    {
        $config = XsstTeenPattiConfig::findOne($params['id']);
        if (empty($config)) {
            return [false, '当前配置不存在'];
        }
        $scaleConfig = $this->setScaleConfig($params);

        [$res, $msg] = XsstTeenPattiConfig::edit($params['id'], [
            'scale_config' => json_encode($scaleConfig)
        ]);

        if (!$res) {
            return [false, $msg];
        }

        return [true, [
            'type' => 0,
            'before_json' => [
                'scale_config' => json_decode($config['scale_config'], true),
            ],
            'after_json' => [
                'scale_config' => $scaleConfig,
            ]
        ]];
    }

    private function setGearConfig(array $params)
    {
        $config = [];
        $search = XsstTeenPattiConfig::GEAR_PREFIX;
        foreach ($params as $key => $val) {
            if (preg_match("/{$search}/i", $key) && !empty($val)) {
                $config[] = (int) $val;
            }
        }
        return $config;
    }

    private function setScaleConfig(array $params)
    {
        $houseOwner = (int) ($params['house_owner'] ?? 0);
        $platform   = (int) ($params['platform'] ?? 0);
        if ($houseOwner < 0 || $platform < 0 || ($houseOwner + $platform > 100)) {
            throw new ApiException(ApiException::MSG_ERROR, '分成值不能为负数，且相加不能超过100');
        }
        return [
            'house_owner' => $houseOwner,
            'platform'    => $platform,
            'winner' => 100 - ($houseOwner + $platform)
        ];
    }

    public function formatGearList($list)
    {
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('teenpattiplaygear', $ids);
        foreach ($list['data'] as &$item) {
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $config = json_decode($item['gear_config'], true);
            if ($config) {
                $item = array_merge($item, $this->handleGearConfig($config));
            }
        }
        return $list;
    }

    public function handleGearConfig($config)
    {
        $data = [];
        foreach ($config as $key => $val) {
            $gearKey = XsstTeenPattiConfig::GEAR_PREFIX . ($key + 1);
            $data[$gearKey] = $val;
        }
        return $data;
    }

    public function formatScaleList($list)
    {
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('teenpattiplayscale', $ids);
        foreach ($list['data'] as &$item) {
            $item['admin_name'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $config = json_decode($item['scale_config'], true);
            if ($config) {
                $item = array_merge($item, $config);
            }
        }
        return $list;
    }
}