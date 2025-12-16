<?php

namespace Imee\Service\Operate\User;

use Imee\Models\Xs\XsUserMobile;
use Imee\Models\Xs\XsUserSafeMobile;
use Imee\Service\Helper;

class MobileRelateService
{
    public function getListAndTotal(array $params): array
    {
        $mobile = trim(array_get($params, 'mobile', ''));
        if (empty($mobile)) {
            return [false, '请输入手机号'];
        }

        $query = [];
        $query[] = ['mobile', '=', $mobile];

        $data = [];
        $lists = XsUserSafeMobile::getListByWhere($query);

        foreach ($lists as $item) {
            $item['app_name'] = Helper::getAppName($item['app_id'] ?? '');
            $item['dateline'] = $item['dateline'] ? date('Y-m-d H:i:s', $item['dateline']) : $item['dateline'];
            $item['is_safe'] = 1;
            $data[] = $item;
        }

        $lists = XsUserMobile::getListByWhere($query);
        foreach ($lists as $item) {
            $item['app_name'] = Helper::getAppName($item['app_id'] ?? '');
            $item['dateline'] = $item['dateline'] ? date('Y-m-d H:i:s', $item['dateline']) : $item['dateline'];
            $item['is_safe'] = 0;
            $data[] = $item;
        }

        return ['data' => $data, 'total' => count($data)];
    }
}