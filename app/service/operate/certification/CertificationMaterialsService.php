<?php

namespace Imee\Service\Operate\Certification;

use Imee\Models\Xs\XsCertificationSign;
use Imee\Service\Helper;

class CertificationMaterialsService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);

        $list = XsCertificationSign::getListAndTotal($conditions, '*', 'id desc', $params['page'], $params['limit']);

        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['label'] = Helper::getHeadUrl($item['label']);
            $item['icon']  = Helper::getHeadUrl($item['icon']);
        }

        return $list;
    }

    public function add(array $params): array
    {
        $time = time();
        $data = [
            'name'        =>  $params['name'] ?? '',
            'icon'        =>  $params['icon'] ?? '',
            'label'       =>  $params['label'] ?? '',
            'dateline'    =>  $time,
            'font_color'  =>  $params['font_color'] ?? '',
            'default_content' => $params['default_content'] ?? '',
            'create_dateline' => $time,
        ];

        list($res, $id) = XsCertificationSign::add($data);
        if (!$res) {
            return [false, $id];
        }
        $data['id'] = $id;
        return [true, ['before_json' => [], 'after_json' => $data]];
    }

    public function modify(array $params): array
    {
        $time = time();

        $info = $this->info($params['id'] ?? 0);

        if (empty($info)) {
            return [false, '当前素材不存在'];
        }

        $data = [
            'name'        =>  $params['name'] ?? '',
            'icon'        =>  $params['icon'] ?? '',
            'label'       =>  $params['label'] ?? '',
            'dateline'    =>  $time,
            'font_color'  =>  $params['font_color'] ?? '',
            'default_content' => $params['default_content'] ?? '',
        ];

        list($res, $msg) = XsCertificationSign::edit($params['id'], $data);
        if (!$res) {
            return [false, $msg];
        }
        $data['id'] = $params['id'];
        return [true, ['before_json' => $info, 'after_json' => $data]];
    }

    public function info(int $id): array
    {
        return XsCertificationSign::findOne($id);
    }

    public function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['name', 'like', "%{$params['name']}%"];
        }

        return $conditions;
    }
}