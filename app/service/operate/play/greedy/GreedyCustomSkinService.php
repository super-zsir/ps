<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class GreedyCustomSkinService
{
    const STATUS_WAIT = 1;
    const STATUS_HAVE = 2;
    const STATUS_END = 3;

    public static $statusMap = [
        self::STATUS_WAIT => '未生效',
        self::STATUS_HAVE => '生效中',
        self::STATUS_END  => '已过期',
    ];

    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params)
    {
        $query = $this->buildQuery($params);
        list($res, $msg, $data) = $this->rpcService->queryGreedySkinList($query);
        $this->handleRpcResponse($res, $msg);
        foreach ($data['data'] as &$item) {
            $item['effective_time'] = Helper::now($item['effective_time']);
            $item['expired_time'] = Helper::now($item['expired_time']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['title_img_obj'] = $this->formatImageObject($item['title_img']);
            $item['theme_img_obj'] = $this->formatImageObject($item['theme_img']);
            $item['bg_img_obj'] = $this->formatImageObject($item['bg_img']);
            $item['open_prize_theme_img_obj'] = $this->formatImageObject($item['open_prize_theme_img']);
            $item['title_img'] = Helper::getUrlPath($item['title_img']);
            $item['theme_img'] = Helper::getUrlPath($item['theme_img']);
            $item['bg_img'] = Helper::getUrlPath($item['bg_img']);
            $item['period_time_day'] = ceil($item['period_time'] / 86400);
        }

        return $data;
    }

    private function formatImageObject(string $image): array
    {
        return [
            'title'        => '下载',
            'url'          => Helper::getHeadUrl($image),
            'type'         => 'url',
            'resourceType' => 'static'
        ];
    }

    private function buildQuery(array $params): array
    {
        $query = [
            'page_size' => intval($params['limit'] ?? 15),
            'page_num'  => intval($params['page'] ?? 1),
        ];

        $id = intval($params['id'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $name = trim($params['name'] ?? '');
        $status = intval($params['status'] ?? 0);
        $startTime = trim($params['dateline_sdate'] ?? '');
        $endTime = trim($params['dateline_edate'] ?? '');

        $id && $query['id'] = $id;
        $uid && $query['uid'] = $uid;
        $name && $query['name'] = $name;
        $status && $query['status'] = $status;
        $startTime && $query['start_time'] = strtotime($startTime);
        $endTime && $query['end_time'] = strtotime($endTime) + 86399;

        return $query;
    }

    public function create(array $params): array
    {
        $data = $this->buildData($params);
        list($res, $msg) = $this->rpcService->createGreedySkin($data);
        $this->handleRpcResponse($res, $msg);

        return ['skin_id' => $msg, 'after_json' => $data];
    }

    public function modify(array $params): array
    {
        $data = $this->buildData($params);
        list($res, $msg) = $this->rpcService->editGreedySkin($data);
        $this->handleRpcResponse($res, $msg);

        return ['skin_id' => $data['skin_id'], 'after_json' => $data];
    }

    private function buildData(array $params): array
    {
        return [
            'id'                   => intval($params['id'] ?? 0),
            'skin_id'              => strval($params['skin_id'] ?? ''),
            'uid'                  => (int)$params['uid'],
            'name'                 => $params['name'],
            'title_img'            => $params['title_img'],
            'theme_img'            => $params['theme_img'],
            'bg_img'               => $params['bg_img'],
            'open_prize_theme_img' => $params['open_prize_theme_img'],
            'title'                => $params['title'],
            'effective_time'       => strtotime($params['effective_time']),
            'period_time'          => intval($params['period_time_day']) * 86400,
        ];
    }

    public function delete(array $params): array
    {
        $data = $this->buildDeleteData($params);
        list($res, $msg) = $this->rpcService->delGreedySkin($data);
        $this->handleRpcResponse($res, $msg);

        return ['skin_id' => $data['skin_id'], 'after_json' => $data];
    }

    public function expire(array $params): array
    {
        $data = $this->buildDeleteData($params);
        list($res, $msg) = $this->rpcService->expireGreedySkin($data);
        $this->handleRpcResponse($res, $msg);

        return ['skin_id' => $data['skin_id'], 'after_json' => $data];
    }


    private function buildDeleteData(array $params): array
    {
        return [
            'skin_id' => (string)$params['skin_id'],
            'uid'     => (int)$params['uid'],
        ];
    }

    public function getStatusMap()
    {
        return StatusService::formatMap(self::$statusMap, 'label,value');
    }

    private function handleRpcResponse($res, $msg)
    {
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'rpc error : ' . $msg);
        }
    }
}