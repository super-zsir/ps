<?php

namespace Imee\Service\Operate\Func;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xs\XsQuickGiftConfig;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class QuickGiftConfigService
{
    public function getList(array $params, int $type): array
    {
        $list = XsQuickGiftConfig::getListAndTotal([
            ['type', '=', $type]
        ], '*','bigarea_id asc', $params['page'], $params['limit']);

        if (empty($list['data'])) {
            return $list;
        }
        $giftIds = array_column($list['data'], 'gift_id');
        $gifts = XsGift::getBatchCommon($giftIds, ['id', 'name'], 'id');
        foreach ($list['data'] as &$item) {
            $item['model_id']   = $item['bigarea_id'];
            $item['name']       = $item['bigarea_id'];
            $item['quick_gift'] = $item['gift_id'] . '-' . ($gifts[$item['gift_id']]['name'] ?? '');
            $item['gift_image'] = Helper::getHeadUrl('/static/gift_big/' . $item['gift_id'] . '.png');
        }

        return $list;
    }

    public function modify(array $params, int $type): array
    {
        $info = XsQuickGiftConfig::findOneByWhere([
            ['bigarea_id', '=', $params['bigarea_id']],
            ['type', '=', $type]
        ]);

        if (empty($info)) {
            return [false, '当前配置不存在'];
        }

        $name = XsBigarea::findOne($params['bigarea_id'])['name'] ?? '';
        if (empty($name) || $this->checkGift($name, $params['gift_id'])) {
            return [false, '该大区不能配置当前礼物'];
        }

        $gid = $params['gift_id'];
        $gift = XsGift::findOne($gid);
        if ($gift['is_secret_gift'] == 1) {
            return [false, '该礼物已配置成私密礼物，无法配置成快捷礼物'];
        }

        $data = [
            'bigarea_id' => (int) $params['bigarea_id'],
            'gift_id'    => (int) $params['gift_id'],
            'status'     => (int) $params['status'],
            'quick_gift_type' => $type
        ];

        list($res, $msg) = (new PsService())->updateQuickGiftConfig($data);

        if (!$res) {
            return [false, $msg];
        }
        unset($data['quick_gift_type']);
        return [true, ['before_json' => $info, 'after_json' => $data]];
    }

    private function checkGift($name, $giftId)
    {
        if ($name == 'cn') {
            $where = "excludes like '%zh_cn%' or excludes like '%zh_tw%'";
        } else {
            $where = "excludes like '%{$name}%'";
        }
        return XsGift::findFirst([
            "conditions" => "id = :id: and deleted =:deleted: and ({$where})",
            "bind" => ["id" => $giftId, "deleted" => XsGift::DELETE_NO],
        ]);
    }

    public function getGift(array $params): array
    {
        $str = $params['str'] ?? '';

        if (empty($str)) {
            return [];
        }

        $conditions = $map = [];

        if (is_numeric($str)) {
            $conditions[] = ['id', '=', $str];
        } else {
            $conditions[] = ['name', 'like', "%{$str}%"];
        }

        $conditions[] = ['deleted', '=', XsGift::DELETE_NO];

        $list = XsGift::getListByWhere($conditions, 'id,name');
        foreach ($list as $v) {
            $map[] = [
                'label' => $v['id'] . '-' . $v['name'],
                'value' => $v['id']
            ];
        }
        return $map;
    }
}