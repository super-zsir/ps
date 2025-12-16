<?php

namespace Imee\Service\Commodity;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsCommodityAdmin;
use Imee\Models\Xsst\BmsCommodityVerify;

class CommodityVerifyService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $cid = intval(array_get($params, 'cid', 0));

        $query = [];
        $cid && $query[] = ['cid', '=', $cid];
        $data = BmsCommodityVerify::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $rec['expired'] = $rec['expire_time'] <= time() ? 1 : 0;
            $rec['expire_time'] = $rec['expire_time'] ? date('Y-m-d H:i:s', $rec['expire_time']) : '';
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        $cid = intval(array_get($data, 'cid', 0));
        $commodityVerify = BmsCommodityVerify::findOneByWhere([['cid', '=', $cid]]);
        if (empty($commodityVerify)) {
            list($flg, $rec) = BmsCommodityVerify::add($data);
            return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];

        } else {
            $id = array_get($commodityVerify, 'id');
            list($flg, $rec) = BmsCommodityVerify::edit($id, $data);
            return [$flg, $flg ? ['id' => $id, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
        }
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = BmsCommodityVerify::findOne($id);
        if (empty($setting)) {
            throw new ApiException(ApiException::VALIDATION_ERROR, ['ID数据错误']);
        }

        $update = ['expire_time' => time() - 1];
        list($flg, $rec) = BmsCommodityVerify::updateByWhere([['id', '=', $id]], $update);
        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $update)] : $rec];
    }

    private function validateAndFormatData($params)
    {
        $cid = intval(array_get($params, 'cid', 0));

        $expireTime = trim(array_get($params, 'expire_time', ''));
        $expireTime = $expireTime ? strtotime($expireTime) : 0;

        if ($expireTime < time()) {
            throw new ApiException(ApiException::MSG_ERROR, '过期时间必须大于当前时间');
        }
        $commodity = XsCommodityAdmin::findOne($cid);
        if (empty($commodity)) {
            throw new ApiException(ApiException::MSG_ERROR, '物品CID错误');
        }

        return [
            'cid'         => $cid,
            'expire_time' => $expireTime,
        ];
    }
}