<?php

namespace Imee\Service\Operate;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;

class PrivateMsgPurviewLevelService
{
    public function getList(array $params): array
    {
        $list = XsBigarea::getListAndTotal([], 'id, name, private_msg_permission_level_config');
        if (empty($list['data'])) {
            return $list;
        }
        $bigAreaIds = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('privatemsgpurviewlevel', $bigAreaIds);
        foreach ($list['data'] as &$item) {
            $item['name'] = XsBigarea::getBigAreaCnName($item['name']);
            $config = json_decode($item['private_msg_permission_level_config'], true);
            $item['text_level'] = $config['text_level'] ?? 0;
            $item['voice_level'] = $config['voice_level'] ?? 0;
            $item['img_level'] = $config['img_level'] ?? 0;
            $item['update_name'] = $logs[$item['id']]['operate_name'] ?? '';
            $item['update_time'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
        }
        return $list;
    }

    public function modify(array $params)
    {
        $config = [
            'text_level'  => (int) $params['text_level'],
            'voice_level' => (int) $params['voice_level'],
            'img_level'   => (int) $params['img_level'],
        ];

        [$res, $msg] = XsBigarea::edit($params['id'], ['private_msg_permission_level_config' => json_encode($config)]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }
}