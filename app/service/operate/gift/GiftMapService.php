<?php

namespace Imee\Service\Operate\Gift;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsCommodityTag;
use Imee\Models\Xs\XsDiyGiftGroup;
use Imee\Models\Xs\XsGift;
use Imee\Service\StatusService;

class GiftMapService
{
    public function getTabMap($value = null)
    {
        $map = XsGift::$tabIdMap;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getIncomeTypeMap(): array
    {
        return StatusService::formatMap(XsGift::$incomeTypes, 'label,value');
    }

    public function getGiftTypeMap($value = null)
    {
        $map = XsGift::$giftTypes;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getTypeMap($value = null)
    {
        $map = XsGift::$types;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getExcludesMap($value = null)
    {
        $map = [];

        foreach (XsGift::$excludes as $lan => $txt) {
            $map[$lan] = $txt . "({$lan})";
        }

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getDeletedMap($value = null)
    {
        $map = XsGift::$deletedMap;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getTagUrlMap(): array
    {
        return StatusService::formatMap(XsGift::$tagUrlMap, 'label,value');
    }

    public function getVapTypeMap(): array
    {
        $map = XsGift::$vapTypeMap;
        return StatusService::formatMap($map, 'label,value');
    }

    public function getXtypeMap(): array
    {
        return StatusService::formatMap(XsGift::$xtypes, 'label,value');
    }

    public function getYesNoMap($value = null)
    {
        $map = XsGift::$yesNoMap;

        if ($value !== null) {
            return $map[$value] ?? $value;
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getExtraParentTypeMap(): array
    {
        return StatusService::formatMap(XsGift::$extraParentTypeMap, 'label,value');
    }

    public function getExtraSubTypeMap(): array
    {
        return StatusService::formatMap(XsGift::$allExtraSubTypeMap, 'label,value');
    }

    public function getSubscribeMap(): array
    {
        return StatusService::formatMap(XsGift::$SubscribeMap, 'label,value');
    }

    public function getSubDisplayMap($value = null)
    {
        $map = XsGift::$subDisplays;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getTagMap(): array
    {
        $map = XsCommodityTag::getListByWhere([['app_id', '=', APP_ID]], 'id,name');
        $map = array_column($map, 'name', 'id');

        return StatusService::formatMap($map, 'label,value');
    }

    public function getDisplayMap($value = null)
    {
        $map = XsGift::$displays;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getRewardTypeMap(): array
    {
        $map = XsGift::$rewardTypeMap;
        return StatusService::formatMap($map, 'label,value');
    }

    public function getDiyTypeMap(): array
    {
        $map = XsGift::$diyTypeMap;
        return StatusService::formatMap($map, 'label,value');
    }

    public function getDiyGroupMap(): array
    {
        $map = XsDiyGiftGroup::getListByWhere([['app_id', '=', APP_ID]], 'id');
        return StatusService::formatMap(array_column($map, 'id', 'id'));
    }

    public function getUnitySignMap(): array
    {
        return StatusService::formatMap(XsGift::$unitySignMap);
    }

    public function getRelationGiftTypeMap(): array
    {
        return StatusService::formatMap(XsGift::$relationGiftType);
    }

    public function getRelationTypeMap(): array
    {
        return StatusService::formatMap(XsGift::$relationType);
    }

    public function getRelationLvMap(): array
    {
        return StatusService::formatMap(XsGift::$relationLv);
    }

    public function getBigareaMap(): array
    {
        $bigareas = XsBigarea::getAreaList();
        $bigareas = array_column($bigareas, 'cn_name', 'name');
        return StatusService::formatMap($bigareas);
    }

    public function getPrivilegeTypeMap($value = null)
    {
        $map = XsGift::$privilegeType;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? '';
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getFamilyLevelMap($value = null)
    {
        $map = XsGift::$familyLevel;

        if ($value !== null) {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map, 'label,value');
    }

    public function getGiftSearchMap(): array
    {
        $map = [
            1  => '幸运礼物',
            2  => '冠名礼物',
            4  => '连击礼物',
            5  => '皮肤礼物',
            6  => '麦位互动礼物',
            7  => '热门礼物',
            8  => '关系礼物',
            9  => '定制礼物',
            10 => '特权礼物',
            11 => '盲盒礼物',
            12 => '私密礼物',
        ];

        return StatusService::formatMap($map);
    }

    public function getGiftMap($value = null)
    {
        $condition = [];
        $data = XsGift::getListByWhere($condition, 'id,name', 'id desc');
        foreach ($data as &$item) {
            $item['name'] = $item['id'] . ' - ' . $item['name'];
        }

        $map = array_column($data, 'name', 'id');

        if ($value !== null && $value != 'offline') {
            if (is_array($value)) {
                $display = '';
                foreach ($value as $v) {
                    $display .= ($map[$v] ?? $v) . ',';
                }
                return trim($display, ',');
            } else {
                return $map[$value] ?? $value;
            }
        }

        return StatusService::formatMap($map);
    }

    public function getOfflineGiftMap($value = null)
    {
        $condition = [];
        $data = XsGift::getListByWhere($condition, 'id,name', 'id desc');
        foreach ($data as &$item) {
            $item['name'] = $item['id'] . ' - ' . $item['name'];
        }

        return StatusService::formatMap(array_column($data, 'name', 'id'));
    }
}