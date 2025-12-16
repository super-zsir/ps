<?php

namespace Imee\Service\Operate\Roombackground;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xs\XsChatroomMaterial;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class BackgroundService
{
    /**
     * @var PsService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $list = $this->rpcService->chatroomMaterialList($params);
        foreach ($list['data'] as &$item) {
            $item['area_in_use'] = empty($item['area_in_use']) ? 'no use' : $item['area_in_use'];
            $item['image'] = Helper::getHeadUrl($item['image']);
            $item['cover'] = Helper::getHeadUrl($item['cover']);
            $item['is_free'] = (string)$item['is_free'];
            if ($item['source'] == 1) {
                $item['is_free'] = 2;
            }
        }
        return $list;
    }

    public function create(array $params): array
    {
        $data = [
            'name' => $params['name'] ?? 'custom background',
            'image' => $params['image'],
            'cover' => $params['cover'],
            'is_free' => (int)$params['is_free'],
            'price' => (int)($params['price'] ?? 0),
            'source' => 0  // 0 为管理后台
        ];
        // free为Custom时，使用默认值
        if ($data['is_free'] == 2) {
            $data['name'] = 'custom background';
            $data['price'] = 0;
            $data['is_free'] = 1;
            $data['source'] = 1; // 1 用户定制
        }
        if (empty($data['is_free']) && $data['price'] <= 0) {
            return [false, 'Free为No的情况下Price必填且为正整数'];
        }

        list($res, $msg) = $this->rpcService->addChatroomMaterial($data);

        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($msg, $data, BmsOperateLog::ACTION_ADD);

        return [true, ''];
    }

    public function modify(array $params): array
    {
        $data = [
            'mid' => (int)$params['mid'],
            'name' => $params['name'],
            'is_free' => (int)$params['is_free'],
            'price' => (int)($params['price'] ?? 0),
            'source' => 0
        ];
        // free为Custom时，使用默认值
        if ($data['is_free'] == 2) {
            $data['name'] = 'custom background';
            $data['price'] = 0;
            $data['is_free'] = 1;
            $data['source'] = 1; // 1 用户定制
        }

        if (empty($data['is_free']) && $data['price'] <= 0) {
            return [false, 'Free为No的情况下Price必填且为正整数'];
        }

        list($res, $msg) = $this->rpcService->editChatroomMaterial($data);

        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($data['mid'], $data, BmsOperateLog::ACTION_UPDATE);

        return [true, ''];
    }

    public function delete(int $mid): array
    {
        list($res, $msg) = $this->rpcService->delChatroomMaterial($mid);

        if (!$res) {
            return [false, $msg];
        }

        $this->addLog($mid, '', BmsOperateLog::ACTION_DEL);

        return [true, ''];
    }

    public function addLog($id, $afterJson, $action, $beforeJson = '', $content = '')
    {
        $data = [
            'model_id' => $id,
            'model' => 'chatroom_background',
            'action' => $action,
            'content' => $content,
            'before_json' => $beforeJson,
            'after_json' => $afterJson,
            'operate_id' => Helper::getSystemUid(),
        ];

        OperateLog::addOperateLog($data);
    }

    public function getLogList(array $params)
    {
        if (!isset($params['bg_id']) || empty($params['bg_id'])) {
            return ['data' => [], 'total' => 0];
        }

        $filter = [
            'model' => 'background_goods',
            'model_id' => $params['bg_id']
        ];

        return OperateLog::getListAndTotal($filter, 'created_time desc', $params['page'], $params['limit']);
    }

    public static function getMaterialOption(int $isFree, int $source): array
    {
        $condition = [];

        if (in_array($isFree, [0, 1])) {
            $condition = [
                ['is_free', '=', $isFree],
                ['source', '=', $source]
            ];
        }

        $list = XsChatroomMaterial::getListByWhere($condition, 'mid, name, is_free, price', 'id desc');
        if (empty($list)) {
            return [];
        }
        $map = [];
        foreach ($list as $item) {
            $map[$item['mid']] = $item['mid'] . '-' . $item['name'] . '-' . XsChatroomMaterial::$isFree[$item['is_free']] . '-' . $item['price'];
        }
        return $map;
    }


}