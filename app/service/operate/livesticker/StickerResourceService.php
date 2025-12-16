<?php

namespace Imee\Service\Operate\Livesticker;


use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsRoomSpecialEffectsConfig;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class StickerResourceService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $list = $this->rpcService->getStickerResourceList($params);
        foreach ($list['data'] as &$item) {
            $item['id']       = $item['sticker']['id'];
            $item['name']     = $item['sticker']['name'];
            $item['remark']   = $item['sticker']['remark'];
            $item['model_id'] = $item['sticker']['id'];
            $item['img']      = Helper::getHeadUrl($item['sticker']['img']);
            $item['use_area'] = $this->getAreaName($item['using_big_area_list']);
            $item['resource'] = [
                'title'        => $item['sticker']['name'] . '.bundle资源',
                'value'        => $item['sticker']['name'] . '.bundle资源',
                'type'         => 'url',
                'url'          => Helper::getHeadUrl($item['sticker']['resource']),
                'resourceType' => 'outside'
            ];
            unset($item['sticker']);
            unset($item['using_big_area_list']);
        }

        return $list;
    }

    private function getAreaName($bigAreas)
    {
        if (empty($bigAreas)) {
            return '-';
        }
        $bigAreaList = XsBigarea::getAllNewBigArea();
        $useArea = [];
        foreach ($bigAreas as $area) {
            if (isset($bigAreaList[$area])) {
                array_push($useArea, $bigAreaList[$area]);
            }
        }

        return implode(',', $useArea);
    }

    public function add(array $params)
    {
        $data = [
            'name'     => $params['name'],
            'img'      => $params['img'],
            'resource' => $params['resource'],
            'remark'   => $params['remark'] ?? ''
        ];
        if ($data['remark'] && strlen($data['remark'])  >= 768) {
            throw new ApiException(ApiException::MSG_ERROR, '备注不能过长');
        }

        [$res, $msg, $id] = $this->rpcService->stickerResourceAdd($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    public function edit(array $params)
    {
        $info = XsRoomSpecialEffectsConfig::findOne($params['id']);
        if (!$info) {
            throw new ApiException(ApiException::MSG_ERROR, '素材不存在');
        }
        $data = [
            'id'       => (int) $params['id'],
            'name'     => $params['name'],
            'remark'   => $params['remark'] ?? ''
        ];

        if ($data['remark'] && (strlen($data['remark'])  >= 768)) {
            throw new ApiException(ApiException::MSG_ERROR, '备注不能过长');
        }

        [$res, $msg] = $this->rpcService->stickerResourceEdit($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $afterJson = [
            'name'     => $params['name'],
            'img'      => $info['img'],
            'resource' => $info['resource'],
            'remark'   => $params['remark'],
        ];

        $beforeJson = [
            'name'     => $info['name'],
            'img'      => $info['img'],
            'resource' => $info['resource'],
            'remark'   => $info['remark'],
        ];

        return ['id' => $params['id'], 'after_json' => $afterJson, 'before_json' => $beforeJson];
    }
}