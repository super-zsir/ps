<?php

namespace Imee\Service\Operate\Lighting;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsNameIdLightingStyle;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class NameIdLightingGroupService
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

        $groupId = intval(array_get($params, 'group_id', 0));
        $resourceName = trim(array_get($params, 'resource_name', ''));
        $styleId = intval(array_get($params, 'style_id', 0));

        $query = ['page' => ['page_index' => $page, 'page_size' => $limit]];
        $groupId && $query['group_id'] = $groupId;
        $resourceName && $query['resource_name'] = $resourceName;
        $styleId && $query['style_id'] = $styleId;

        list($flg, $msg, $data) = $this->rpc->listNameIdLightingConfig($query);
        if (!$flg) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        foreach ($data['data'] as &$rec) {
            $_createTime = array_get($rec, 'create_time', 0);
            $_updateTime = array_get($rec, 'update_time', 0);
            $_styleConfig = array_get($rec, 'style_config', []);
            $_resourceName = $rec['resource_name'] ? @json_decode($rec['resource_name'], true) : [];

            $rec['create_time'] = $_createTime ? date('Y-m-d H:i:s', $_createTime) : '';
            $rec['update_time'] = $_updateTime ? date('Y-m-d H:i:s', $_updateTime) : '';

            $rec['resource_icon_url'] = Helper::getHeadUrl($rec['resource_icon'] ?: '');
            $rec['resource_name_format'] = $_resourceName;
            $rec['resource_name_show'] = XsNameIdLightingGroup::formatName($_resourceName);

            if (is_array($_styleConfig)) {

                foreach ($_styleConfig as &$item) {
                    $_styleName = $item['style_name'] ? @json_decode($item['style_name'], true) : [];
                    $item['style_name_format'] = $_styleName;
                    $item['style_name_show'] = XsNameIdLightingGroup::formatName($_styleName);

                    $item['multi_color_direction'] = (string)$item['multi_color_direction'];
                    $item['multi_color_style'] = (string)$item['multi_color_style'];
                    $item['lighting_direction'] = (string)$item['lighting_direction'];
                    $item['lighting_color'] = $this->getColorEnum($item['lighting_color']);
                }
            }
            $rec['style_config'] = $_styleConfig;
        }
        return $data;
    }



    public function getOptions()
    {
        return [
            'multi_color_style' => StatusService::formatMap(XsNameIdLightingStyle::$multiColorStyleMaps),
            'direction'         => StatusService::formatMap(XsNameIdLightingStyle::$directionMaps),
            'lan'               => StatusService::formatMap(XsBigarea::getLanguageArr()),
            'color'             => StatusService::formatMap(XsNameIdLightingStyle::$color),
        ];
    }

    public function getInfo(int $groupId)
    {
        $data = $this->getListAndTotal(['group_id' => $groupId]);
        return isset($data['data'][0]) ? $data['data'][0] : [];
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $msg, $data) = $this->rpc->setNameIdLightingConfig($data);
        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'group_id', 0);
        $setting = XsNameIdLightingGroup::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $data = $this->validateAndFormatData($params);
        list($flg, $msg, $data) = $this->rpc->setNameIdLightingConfig($data);
        return [$flg, $flg ? ['after_json' => array_merge($data)] : $msg];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsNameIdLightingGroup::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $flg = XsNameIdLightingGroup::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    private function validateAndFormatData($params): array
    {
        $groupId = (int)array_get($params, 'group_id', 0);
        $resourceName = array_get($params, 'resource_name', []);
        $resourceIcon = trim(array_get($params, 'resource_icon', ''));
        $styleConfig = array_get($params, 'style_config', []);
        $adminId = (int)array_get($params, 'admin_id', 0);

        $data = [
            'group_id'      => $groupId,
            'resource_name' => @json_encode($resourceName),
            'resource_icon' => $resourceIcon,
            'style_config'  => [],
            'oprater'       => Helper::getAdminName($adminId)
        ];

        if(empty($styleConfig)){
            throw new ApiException(ApiException::MSG_ERROR, '请至少添加一条资源预设样式');
        }

        foreach ($styleConfig as $item) {

            $color = $item['color'] ?? '';
            $color = explode(',', $color);

            $data['style_config'][] = [
                'style_id'              => intval($item['style_id'] ?? 0),
                'style_name'            => @json_encode($item['style_name'] ?? []),
                'color'                 => $color,
                'color_str'             => trim($item['color_str'] ?? ''),
                'multi_color_direction' => intval($item['multi_color_direction'] ?? 0),
                'multi_color_style'     => intval($item['multi_color_style'] ?? 0),
                'lighting_direction'    => intval($item['lighting_direction'] ?? 0),
                'lighting_color'        => isset($item['lighting_color']) && isset(XsNameIdLightingStyle::$color[$item['lighting_color']]) ? XsNameIdLightingStyle::$color[$item['lighting_color']] : []
            ];
        }

        return $data;
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