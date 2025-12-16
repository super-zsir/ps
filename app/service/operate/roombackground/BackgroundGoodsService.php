<?php

namespace Imee\Service\Operate\Roombackground;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsChatroomMaterial;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class BackgroundGoodsService
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
        $list = $this->rpcService->chatroomBackgroundMallList($params);
        foreach ($list['data'] as &$item) {
            $item['duration'] = $this->formatDuration($item['duration']);
            $item['cover'] = !empty($item['cover']) ? Helper::getHeadUrl($item['cover']) : Helper::getHeadUrl($item['image']);
            $item['is_free'] = (string) $item['is_free'];
            $item['state'] = (string) $item['state'];
            $item['effective_time'] = empty($item['effective_time']) ? '' : Helper::now($item['effective_time']);
        }
        return $list;
    }

    private function formatDuration($duration)
    {
        if (empty($duration)) {
            return 0;
        }

        return ceil(((int) $duration) / 86400);
    }

    public function create(array $params): array
    {
        $data = [
            'mid' => (int) $params['mid'],
            'area' => $params['big_area'],
            'cover' => $params['cover'] ?? '',
            'duration' => ((int) $params['duration']) * 86400,
            'state' => (int) $params['state'],
            'sn' => (int) $params['sn'],
            'name' => $params['name'],
        ];

        $material = XsChatroomMaterial::findOneByWhere([['mid', '=', $data['mid']]]);
        $data['source'] = $material['source'] ?? 0;

        if ($data['state'] && isset($params['effective_time'])) {
            $data['effective_time'] = strtotime($params['effective_time']);
        }

        list($res, $msg) = $this->rpcService->addRoomBackground($data);

        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($msg, $data, BmsOperateLog::ACTION_ADD);

        return [true, ''];
    }

    public function modify(array $params): array
    {
        $data = [
            'bg_id'  => (int) $params['bg_id'],
            'sn'     => (int) $params['sn'],
            'name'   => $params['name'],
            'cover'  => $this->getUrlPath($params['cover']),
            'state'  => (int) $params['state'],
        ];

        if ($data['state'] && isset($params['effective_time'])) {
            $data['effective_time'] = strtotime($params['effective_time']);
        }

        $bgInfo = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $data['bg_id']]]);

        list($res, $msg) = $this->rpcService->editRoomBackground($data);

        if (!$res) {
            return [false, $msg];
        }

        $this->addLog($data['bg_id'], $data, BmsOperateLog::ACTION_UPDATE, $bgInfo);

        return [true, ''];
    }

    public function delete(int $bgId): array
    {
        list($res, $msg) = $this->rpcService->delRoomBackground($bgId);

        if (!$res) {
            return [false, $msg];
        }

        $this->addLog($bgId, '', BmsOperateLog::ACTION_DEL);

        return [true, ''];
    }

    public function addLog($id,  $afterJson, $action, $beforeJson = '', $content = '')
    {
        $data = [
            'model_id'     => $id,
            'model'        => 'background_goods',
            'action'       => $action,
            'content'      => $content,
            'before_json'  => $beforeJson,
            'after_json'   => $afterJson,
            'operate_id'   => Helper::getSystemUid(),
        ];

        OperateLog::addOperateLog($data);
    }

    public function getMaterialMap($string)
    {
        $list = XsChatroomMaterial::getListByWhere([
            ['is_free', '=', 1],
            ['source', '=', 1],
            ['name', 'like', $string]
        ], 'mid, name, is_free, price', 'id desc', 1000);
        if (empty($list)) {
            return [];
        }
        $map = [];
        foreach ($list as $item) {
            $map[$item['mid']] = $item['mid'] . '-' . $item['name'] . '-' . XsChatroomMaterial::$isFree[$item['is_free']] . '-' . $item['price'];
        }
        return StatusService::formatMap($map, 'label,value');
    }

    private function getUrlPath($url)
    {
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return ltrim(parse_url($url)['path'], '/');
        }


        return $url;
    }
}