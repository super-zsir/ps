<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsWebPageResource;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class WebOfflinePackageConfigService
{
    private $guid = 'webofflinepackageconfig';

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
        $query = [
            'page_num' => intval($params['page'] ?? 1),
            'page_size' => intval($params['page_size'] ?? 15),
        ];
        if (isset($params['resource_id']) && !empty($params['resource_id'])) {
            $query['resource_id'] = $params['resource_id'];
        }
        if (isset($params['name']) && !empty($params['name'])) {
            $query['name'] = $params['name'];
        }
        list($res, $msg, $list) = $this->rpcService->queryResourceList($query);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list['data'])) {
            return $list;
        }
        $logs = BmsOperateLog::getFirstLogList($this->guid, Helper::arrayFilter($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $item['update_time'] = Helper::now($logs[$item['id']]['created_time']);
            $item['operator'] = $logs[$item['id']]['operate_name'];
            $item['size'] = $this->formatSize($item['size']);
            $item['log'] = [
                'title'    => '查看',
                'value'    => '查看',
                'type'     => 'manMadeModal',
                'modal_id' => 'table_modal',
                'params'   => [
                    'guid' => 'webofflinepackageconfiglog',
                    'id'   => $item['id']
                ],
            ];
        }

        return $list;
    }

    private function setResourceUrl($url): string
    {
        $parseUrl = parse_url($url);
        return ltrim($parseUrl['path'], '/');
    }

    /**
     * 创建
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function create(array $params): array
    {
        $data = [
            'resource_id'  => $params['resource_id'],
            'name'         => $params['name'],
            'resource_url' => $this->setResourceUrl($params['resource_url']),
            'status'       => (int)$params['status'],
            'force_update' => (int)$params['force_update'],
            'remark'       => $params['remark'] ?? ''
        ];

        $resourceUrl = Helper::getHeadUrl($data['resource_url']);
        $data['size'] = $this->getSize($resourceUrl);
        $zip = @file_get_contents($resourceUrl);
        $data['md5'] = md5($zip);
        list($res, $id) = $this->rpcService->createH5Resource($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $id);
        }

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 编辑
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function modify(array $params): array
    {
        $id = (int)$params['id'];
        $info = XsWebPageResource::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '数据不存在');
        }

        $data = [
            'id'           => (int)$params['id'],
            'resource_id'  => $params['resource_id'],
            'name'         => $params['name'],
            'resource_url' => $this->setResourceUrl($params['resource_url']),
            'status'       => (int)$params['status'],
            'force_update' => (int)$params['force_update'],
            'remark'       => $params['remark'] ?? ''
        ];
        $resourceUrl = Helper::getHeadUrl($data['resource_url']);
        $data['size'] = $this->getSize($resourceUrl);
        $zip = @file_get_contents($resourceUrl);
        $data['md5'] = md5($zip);
        list($res, $msg) = $this->rpcService->editH5Resource($data);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $data['id'], 'before_json' => $info, 'after_json' => $data];
    }

    public function getStatusMap(): array
    {
        $map = [];
        foreach (XsWebPageResource::$statusMap as $key => $val) {
            $map[] = [
                'label' => $val,
                'value' => $key
            ];
        }

        return $map;
    }

    /**
     * 格式化size为MB
     * @param $size
     * @return string
     */
    private function formatSize(int $size): string
    {
        return round($size / 1024 / 1024, 1) . 'M';
    }

    /**
     * 获取资源大小
     * @param $url
     * @return int
     */
    private function getSize($url): int
    {
        $size = Helper::getLinkSize($url);

        if ($size === false) {
            return 0;
        }

        return (int)$size;
    }

    public function getLogList(array $params): array
    {
        $logs = BmsOperateLog::getListByWhere([
            ['model', '=', $this->guid],
            ['model_id', '=', $params['id']],
        ], '*', 'id asc');
        $data = [];
        foreach ($logs as $key => $item) {
            $json = json_decode($item['after_json'], true);
            $json['size'] = $this->formatSize($json['size'] ?? 0);
            $data[] = array_merge($json,
                [
                    'id'           => $key + 1,
                    'created_time' => Helper::now($item['created_time']),
                    'operate_name' => $item['operate_name']
                ]);
        }

        array_multisort($data, SORT_DESC, array_column($data, 'id'));
        return $data;
    }
}