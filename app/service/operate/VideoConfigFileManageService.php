<?php

namespace Imee\Service\Operate;

use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstVideoConfig;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use Imee\Service\Helper;
use Phalcon\Di;

class VideoConfigFileManageService
{
    const CONFIG_TYPE_JSON = 0;
    const CONFIG_TYPE_URL = 1;

    public static $configTypeMap = [
        self::CONFIG_TYPE_JSON => 'text',
        self::CONFIG_TYPE_URL  => 'file',
    ];

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
            'business_type' => 0,
            'page'          => intval($params['page'] ?? 1),
            'page_size'     => intval($params['limit'] ?? 15),
        ];
        $name = $params['name'] ?? '';
        $key = $params['key'] ?? '';
        $name && $query['name'] = $name;
        $key && $query['key'] = $key;
        list($res, $msg, $list) = $this->rpcService->getKv($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list['data'])) {
            return $list;
        }
        foreach ($list['data'] as &$item) {
            $item['config_type'] = (string)$item['config_type'];
            $item['config_type_text'] = self::$configTypeMap[$item['config_type']] ?? '';
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';
            if ($item['config_type'] == self::CONFIG_TYPE_URL) {
                $item['file'] = $item['value'];
                $item['value'] = Helper::getHeadUrl($item['value']);
            } else {
                $item['content'] = $item['value'];
            }
            $item['log'] = [
                'title'    => '操作日志',
                'value'    => '操作日志',
                'type'     => 'manMadeModal',
                'modal_id' => 'table_modal',
                'params'   => [
                    'guid' => 'videoconfigfilemanagelog',
                    'key'  => $item['key']
                ],
            ];
        }
        return $list;
    }

    /**
     * save
     * @param array $params
     * @param bool $isCreate
     * @return array
     * @throws ApiException
     */
    public function save(array $params, bool $isCreate = true): array
    {
        $data = $this->validation($params, $isCreate);
        list($res, $msg) = $this->rpcService->setKv($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    /**
     * delete
     * @param array $params
     * @return array[]
     * @throws ApiException
     */
    public function delete(array $params): array
    {
        $key = $params['key'] ?? '';
        if (empty($key)) {
            throw new ApiException(ApiException::MSG_ERROR, 'key不能为空');
        }
        $data = [
            'business_type' => 0,
            'key'           => $key,
            'status'        => 1, //生效状态 0生效 1删除
        ];
        list($res, $msg) = $this->rpcService->setKv($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['after_json' => $data];
    }

    /**
     * validation
     * @param array $params
     * @param bool $isCreate
     * @return array
     * @throws ApiException
     */
    private function validation(array $params, bool $isCreate): array
    {
        $type = $params['config_type'];
        $value = $params['content'] ?? '';
        $key = $params['key'];

        if ($type == self::CONFIG_TYPE_JSON) {
            $valueArr = @json_decode($value, true);
            if (empty($valueArr)) {
                throw new ApiException(ApiException::MSG_ERROR, '文件内容格式错误，必须为json格式');
            }
        } else if ($type == self::CONFIG_TYPE_URL) {
            $file = $params['file'] ?? '';
            if (empty($file)) {
                throw new ApiException(ApiException::MSG_ERROR, '类型为File, 文件必须上传');
            }
            $value = $file;
        }

        $data = [
            'business_type' => 0,
            'key'           => $key,
            'operator'      => Helper::getAdminName($params['admin_id']),
            'config_type'   => $type,
            'key_name'      => $params['name'],
            'value'         => $value,
            'desc'          => $params['desc'] ?? '',
            'is_create'     => $isCreate
        ];

        return $data;
    }

    public function getTypeMap()
    {
        return StatusService::formatMap(self::$configTypeMap, 'label,value');
    }

    public function getLogList(array $params): array
    {
        $query = [
            'business_type' => 0,
            'key'      => $params['key'] ?? '',
        ];
        list($res, $msg, $list) = $this->rpcService->getKvHistory($query);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        if (empty($list)) {
            return $list;
        }
        foreach ($list as $key => &$item) {
            if ($item['config_type'] == self::CONFIG_TYPE_URL) {
                $item['value'] = Helper::getHeadUrl($item['value']);
            }
            $item['id'] = $key + 1;
            $item['config_type'] = self::$configTypeMap[$item['config_type']] ?? '';
            $item['update_time'] = $item['create_time'] > 0 ? Helper::now($item['create_time']) : '';
        }

        return $list;
    }
}