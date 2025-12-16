<?php

namespace Imee\Controller\Validation\Operate\Commodity;

use Imee\Comp\Common\Validation\Validator;
use Imee\Models\Config\BbcCommodityPriceLevel;

class CommodityPriceLevelValidation extends Validator
{

    protected function rules()
    {
        return [
            'id' => 'integer|min:1',
            'type' => 'required|string|in:' . implode(',', array_keys(BbcCommodityPriceLevel::$types)),
            'level' => 'required|integer|in:' . implode(',', array_keys(BbcCommodityPriceLevel::$level)),
            'price_start' => 'required|integer',
            'price_end' => 'required|integer',
            'price_type' => 'required|integer|in:' . implode(',', array_keys(BbcCommodityPriceLevel::$priceLevel)),
        ];
    }

    protected function attributes()
    {
        return [
            'id' => 'ID',
            'type' => '物品类型',
            'level' => '档位',
            'price_start' => '开始价格',
            'price_end' => '结束价格',
            'price_type' => '价格类型',
        ];
    }

    protected function response()
    {
        return [
            'result' => [
                'success' => true,
                'code' => 0,
                'msg' => '',
                'total' => 1,
                'data' => [
                ],
            ],
        ];
    }

    protected function messages()
    {
        return [];
    }
}