<?php

namespace Imee\Service\Commodity;

use Imee\Exception\ApiException;
use Imee\Models\Config\BbcCommodityPriceLevel;
use Imee\Service\StatusService;

class CommodityPriceLevelService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $data = BbcCommodityPriceLevel::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $create = array_get($rec, 'create_time', 0);
            $rec['create_time'] = $create ? date('Y-m-d H:i', $create) : '';
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = BbcCommodityPriceLevel::add($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BbcCommodityPriceLevel::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $update = [];
        $data = $this->validateAndFormatData($params);
        foreach ($data as $k => $v) {
            if ($v != array_get($setting, $k)) {
                $update[$k] = $v;
            }
        }

        if (count($update)) {
            list($flg, $rec) = BbcCommodityPriceLevel::updateByWhere([['id', '=', $id]], $update);

            return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
        }
        return [false, '数据不需要更新'];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BbcCommodityPriceLevel::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $flg = BbcCommodityPriceLevel::deleteById($id);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : '删除失败'];
    }

    private function validateAndFormatData($params)
    {
        $id = intval(array_get($params, 'id', 0));
        $type = trim(array_get($params, 'type', ''));
        $level = intval(array_get($params, 'level', 0));

        $priceStart = intval(array_get($params, 'price_start', 0));
        $priceEnd = intval(array_get($params, 'price_end', 0));
        $priceType = intval(array_get($params, 'price_type', 0));


        if ($priceStart > $priceEnd || $priceEnd < 0 || $priceStart < 0) {
            throw new ApiException(ApiException::MSG_ERROR, '价格填写有误');
        }
        $filter = [['type', '=', $type], ['level', '=', $level]];
        $id && $filter[] = ['id', '!=', $id];
        $exist = BbcCommodityPriceLevel::findOneByWhere($filter);
        if (!empty($exist)) {
            throw new ApiException(ApiException::MSG_ERROR, '该价格档位已存在，不可重复创建');
        }

        $data = [
            'type' => $type,
            'level' => $level,
            'price_start' => $priceStart,
            'price_end' => $priceEnd,
            'price_type' => $priceType,
        ];
        if (empty($id)) {
            $data['create_time'] = time();
        }
        return $data;
    }

    public static function getTypes($value = null, string $format = '')
    {
        $map = BbcCommodityPriceLevel::$types;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function checkProductPriceLevel($proType, $priceLevel, $price, $days = 1): array
    {
        $list = BbcCommodityPriceLevel::getListByWhere([['type', '=', $proType]]);

        if (!empty($list)) {
            if (!$priceLevel) {
                return [false, '该物品类型下档位必填'];
            }
            $levelArr = array_column($list, null, 'level');
            if (!isset($levelArr[$priceLevel])) {
                return [false, '所选档位有误'];
            }
            $info = $levelArr[$priceLevel];
            if (BbcCommodityPriceLevel::PRICE_TYPE_PER == $info['price_type'] && $days == 0) {
                return [false, '当所选档位类型为单天价格时，有效期天数不得为0'];
            }
            if (BbcCommodityPriceLevel::PRICE_TYPE_PER == $info['price_type'] && $days > 0) {
                $price = round($price / $days, 4);
            }
            if (round($info['price_start'], 4) > $price) {
                return [false, '请填写对应档位的价格'];
            }
            if ($price > round($info['price_end'], 4)) {
                return [false, '请填写对应档位的价格'];
            }
        }
        return [true, ''];
    }

    public static function getDays($days, $hours)
    {
        $rs = 0;
        if (!$days && !$hours) {
            return $rs;
        }
        if ($days > 0) {
            $rs += $days;
        }
        if ($hours > 0) {
            $rs += round($hours / 24, 4);
        }
        return $rs;
    }

    public static function getLevel($value = null, string $format = '')
    {
        $map = BbcCommodityPriceLevel::$level;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

}