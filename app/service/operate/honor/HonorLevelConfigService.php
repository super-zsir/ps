<?php

namespace Imee\Service\Operate\Honor;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsHonorLevelConfig;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsNameIdLightingStyle;
use Imee\Models\Xs\XsUserHonorLevelSendRecord;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class HonorLevelConfigService
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

        $id = (int)array_get($params, 'id', 0);

        $query = ['page' => $page, 'limit' => $limit];
        $id && $query['id'] = $id;

        list($flg, $msg, $data) = $this->rpc->honorLevelConfigList($query);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($data['data'] as &$rec) {
            $_create = $rec['create_time'] ?? 0;
            $_update = $rec['update_time'] ?? 0;
            $_styleConfig = $rec['style_config'] ?? [];

            $rec['level_show'] = sprintf("%d - %d", $rec['min_level'] ?? '', $rec['max_level'] ?? '');
            $rec['create_time'] = $_create ? date('Y-m-d H:i:s', $_create) : '';
            $rec['update_time'] = $_update ? date('Y-m-d H:i:s', $_update) : '';
            $rec['level_icon_show'] = Helper::getHeadUrl($_styleConfig['level_icon'] ?? '');
            $rec['style_icon_show'] = Helper::getHeadUrl($_styleConfig['style_icon'] ?? '');
            $rec['font_color_show'] = implode(',', $_styleConfig['font_color'] ?? []);
        }
        return $data;
    }


    public function getOptions()
    {
        return [
            'shade_style'     => StatusService::formatMap(XsHonorLevelConfig::$shadeStyleMaps),
            'shade_direction' => StatusService::formatMap(XsHonorLevelConfig::$shadeDirectionMaps),
        ];
    }

    public function getInfo(int $id): array
    {
        $data = $this->getListAndTotal(['id' => $id]);
        return isset($data['data'][0]) ? $data['data'][0] : [];
    }

    public function getConfig(int $honorLevel): array
    {
        list($flg, $msg, $data) = $this->rpc->honorLevelGetConfig(['honor_level' => $honorLevel]);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return $data;
    }


    public function add($params): array
    {
        $addData = $this->validateAndFormatData($params);
        list($flg, $msg, $data) = $this->rpc->honorLevelConfigCreate($addData);
        return [$flg, $flg ? ['after_json' => $addData] : $msg];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $setting = XsHonorLevelConfig::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $updateData = $this->validateAndFormatData($params);
        list($flg, $msg, $data) = $this->rpc->honorLevelConfigUpdate($updateData);
        return [$flg, $flg ? ['after_json' => $updateData] : $msg];
    }

    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $minLevel = (int)array_get($params, 'min_level', 0);
        $maxLevel = (int)array_get($params, 'max_level', 0);
        $styleConfig = array_get($params, 'style_config', []);
        $adminId = array_get($params, 'admin_id', '');
        $creator = Helper::getAdminName($adminId);

        if ($maxLevel <= $minLevel) {
            throw new ApiException(ApiException::MSG_ERROR, '等级区间最大值必须大于最小值');
        }

        if (!isset($styleConfig[0]) || !is_array($styleConfig[0])) {
            throw new ApiException(ApiException::MSG_ERROR, 'style_config 配置数据错误');
        }

        $temp = [
            'level_icon'      => $styleConfig[0]['level_icon'] ?? '',
            'style_icon'      => $styleConfig[0]['style_icon'] ?? '',
            'font_color'      => $styleConfig[0]['font_color'] ?? [],
            'color_str'       => $styleConfig[0]['color_str'] ?? '',
            'shade_style'     => intval($styleConfig[0]['shade_style']),
            'shade_direction' => intval($styleConfig[0]['shade_direction']),
        ];

        if ($id) {
            return [
                'config_id'    => $id,
                'style_config' => $temp,
                'operator'     => $creator,
            ];
        } else {

            return [
                'min_level'    => $minLevel,
                'max_level'    => $maxLevel,
                'style_config' => $temp,
                'creator'      => $creator,
            ];
        }
    }

}