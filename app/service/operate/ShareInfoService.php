<?php
/**
 * 分享文案管理服务
 */

namespace Imee\Service\Operate;

use Imee\Models\Config\BbcShareInfo;
use Imee\Service\Helper;

class ShareInfoService
{
    public function getListAndTotal($params, $page, $limit): array
    {
        $conditions = [];
        if (!empty($params['type'])) {
            $conditions[] = ['type', '=', $params['type']];
        }
        if (!empty($params['language'])) {
            $conditions[] = ['language', '=', $params['language']];
        }
        if (isset($params['deleted'])) {
            $conditions[] = ['deleted', '=', $params['deleted']];
        }

        $res = BbcShareInfo::getListAndTotal($conditions, '*', 'id desc', $page, $limit);
        if (!$res['data']) {
            return $res;
        }
        $this->packListData($res['data']);

        return $res;
    }

    private function packListData(&$data)
    {
        $options = $this->getOptions();
        foreach ($data as &$val) {
            $val['type_name'] = $options['types'][$val['type']];
            $val['language_name'] = $options['languages'][$val['language']];
            $val['dateline'] = $val['dateline'] ? date('Y-m-d H:i', $val['dateline']) : '-';
        }
    }

    public function add($params): bool
    {
        return BbcShareInfo::insert($params);
    }

    public function edit($id, $params): bool
    {
        return BbcShareInfo::modify($id, $params);
    }

    public function getOptions(): array
    {
        $data = [];
        $data['languages'] = Helper::getLanguageArr();
        $data['types'] = BbcShareInfo::getTypes();
        return $data;
    }
}