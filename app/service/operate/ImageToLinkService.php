<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Config\BbcWebTitle;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;

class ImageToLinkService
{
    public function getList(array $params): array
    {
        $conditions = $this->getConditions($params);

        $list = BbcWebTitle::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }
        $ids = array_column($list['data'], 'id');
        $logs = BmsOperateLog::getFirstLogList('imagetolink', $ids);

        foreach ($list['data'] as &$item) {
            $item['update_time'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';
            $item['update_uname'] = $logs[$item['id']]['operate_name'] ?? '';
            $item['create_uname'] = Helper::getAdminName($item['admin_id']);
            $item['image_route'] = Helper::getHeadUrl($item['image']);
            $item['image_path'] = Helper::getHeadUrl($item['image']);
            $item['create_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';
        }

        return $list;
    }

    public function add(array $params)
    {
        $data = [
            'title' => $params['title'],
            'image' => $params['image'],
            'admin_id'  => Helper::getSystemUid(),
            'create_time' => time(),
            'update_time' => time(),
        ];

        list($res, $id) = BbcWebTitle::add($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id || '添加失败');
        }

		$link = ENV == 'dev' ? BbcWebTitle::$linkDevPrefix : BbcWebTitle::$linkPrefix;
        // 更新link字段
        BbcWebTitle::edit($id, [
           'link' => sprintf($link, $id)
        ]);

        return $id;
    }

    public function edit(array $params)
    {
        $data = [
            'title' => $params['title'],
            'image' => $params['image']
        ];

        list($res, $msg) = BbcWebTitle::edit($params['id'], $data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function getConditions(array $params): array
    {
        $conditions = [];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }
        if (isset($params['title']) && !empty($params['title'])) {
            $conditions[] = ['title', 'like', "%{$params['title']}%"];
        }
        
        return $conditions;
    }
}