<?php

namespace Imee\Service\Operate\Multilang;

use Imee\Models\Rpc\PsRpc;
use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class MultilangService
{
    private $psRpc;

    const PACKAGE = '';

    private static $groupMap = [
        1 => '客户端',
        2 => '服务端',
        3 => 'web端',
        0 => '3端公用',
    ];

    private static $languageMp = [
        'zh_cn' => 'zh_cn',
        'cn'    => 'zh_tw',
        'en'    => 'en',
        'ko'    => 'ko',
        'ms'    => 'ms',
        'th'    => 'th',
        'id'    => 'id',
        'vi'    => 'vi',
        'ar'    => 'ar',
        'tr'    => 'tr',
        'hi'    => 'hi',
        'ur'    => 'ur',
        'bn'    => 'bn',
        'tl'    => 'tl',
    ];

    public function __construct()
    {
        $this->psRpc = new PsRpc();
    }

    public function getList($params)
    {
        $data = [
            'key'       => $params['key'] ?? '',
            'version'   => $params['version'] ?? '',
            'pageIndex' => intval($params['page'] ?? 1),
            'pageSize'  => intval($params['limit'] ?? 15),
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_LIST_CONFIG, ['query' => $data]);
        $list = $res['configs'] ?? [];
        $langMap = self::$languageMp;
        $result = [];
        foreach ($list as $row) {
            $item = $row;
            // 将 id 重命名为 rid
            if (isset($item['id'])) {
                $item['rid'] = $item['id'];
                unset($item['id']);
            }
            if (isset($row['config']) && is_array($row['config'])) {
                foreach ($langMap as $langKey => $configKey) {
                    if (isset($row['config'][$configKey])) {
                        $item[$langKey] = $row['config'][$configKey];
                    }
                }
            }

            $item['create_time'] = Helper::now($item['create_time']);
            $item['update_time'] = $item['update_time'] > 0 ? Helper::now($item['update_time']) : '';

            unset($item['config']);
            $result[] = $item;
        }
        return [
            'data'  => $result,
            'total' => $res['total'] ?? 0,
        ];
    }

    public function create($params)
    {
        $key = $params['key'] ?? '';
        if (empty($key)) {
            throw new ApiException(ApiException::MSG_ERROR, 'key不能为空');
        }
        if (empty($params['version'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'version不能为空');
        }
        if (!isset($params['group'])) {
            $params['group'] = 0;
        }
        if (!is_numeric($params['group'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'group必须是数字');
        }
        $group = (int)$params['group'];
        if (!array_key_exists($group, self::$groupMap)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group不合法');
        }
        // 查询详情
        list($getRes, $_) = $this->psRpc->call(PsRpc::API_I18N_GET_CONFIG, ['query' => ['key' => $key]]);
        if (isset($getRes['common']['err_code']) && $getRes['common']['err_code'] == 0 && !empty($getRes['config']['key'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'key已存在');
        }
        // 组装 config
        $config = [];
        foreach (self::$languageMp as $langKey => $configKey) {
            $value = $params[$langKey] ?? '';
            // 清理和验证UTF-8字符
            $value = $this->cleanUtf8String($value, 1, $langKey);
            $config[$configKey] = $value;
        }
        $data = [
            'data' => [
                'id'      => isset($params['rid']) ? (int)$params['rid'] : 0,
                'key'     => $params['key'],
                'version' => $params['version'],
                'group'   => $group,
                'config'  => $config,
                'creator' => Helper::getSystemUserInfo()['user_name'] ?? '',
                'updater' => Helper::getSystemUserInfo()['user_name'] ?? '',
            ]
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_CREATE_CONFIG, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '创建失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $res['id'] ?? 0, 'after_json' => $data['data']];
    }

    public function batchCreate($params)
    {
        if (empty($params['list']) || !is_array($params['list'])) {
            throw new ApiException(ApiException::MSG_ERROR, '批量数据不能为空');
        }
        if (count($params['list']) > 5000) {
            throw new ApiException(ApiException::MSG_ERROR, '批量数据不能超过5000条');
        }
        $user = Helper::getSystemUserInfo()['user_name'] ?? '';
        $configs = [];
        foreach ($params['list'] as $index => $item) {
            $rowNum = $index + 1;
            $groupResult = $this->convertGroupValue($item['group'] ?? 0, $rowNum);
            if ($groupResult['error'] !== null) {
                throw new ApiException(ApiException::MSG_ERROR, $groupResult['error']);
            }
            $group = $groupResult['value'];
            if (empty($item['key'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：key不能为空");
            }
            if (empty($item['version'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：version不能为空");
            }
            // 组装 config
            $config = [];
            foreach (self::$languageMp as $langKey => $configKey) {
                $value = $item[$langKey] ?? '';
                // 清理和验证UTF-8字符
                $value = $this->cleanUtf8String($value, $rowNum, $langKey);
                $config[$configKey] = $value;
            }
            $configs[] = [
                'id'      => 0,
                'key'     => $item['key'],
                'version' => $item['version'],
                'group'   => $group,
                'config'  => $config,
                'creator' => $user,
                'updater' => $user,
            ];
        }
        $data = ['configs' => $configs];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_BATCH_CREATE_CONFIG, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '批量创建失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return true;
    }

    public function batchUpdate($params)
    {
        if (empty($params['list']) || !is_array($params['list'])) {
            throw new ApiException(ApiException::MSG_ERROR, '批量数据不能为空');
        }
        if (count($params['list']) > 5000) {
            throw new ApiException(ApiException::MSG_ERROR, '批量数据不能超过5000条');
        }
        $user = Helper::getSystemUserInfo()['user_name'] ?? '';
        $configs = [];
        foreach ($params['list'] as $index => $item) {
            $rowNum = $index + 1;
            $groupResult = $this->convertGroupValue($item['group'] ?? 0, $rowNum);
            if ($groupResult['error'] !== null) {
                throw new ApiException(ApiException::MSG_ERROR, $groupResult['error']);
            }
            $group = $groupResult['value'];
            if (empty($item['key'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：key不能为空");
            }
            if (empty($item['version'])) {
                throw new ApiException(ApiException::MSG_ERROR, "第{$rowNum}行：version不能为空");
            }
            // 组装 config
            $config = [];
            foreach (self::$languageMp as $langKey => $configKey) {
                $value = $item[$langKey] ?? '';
                // 清理和验证UTF-8字符
                $value = $this->cleanUtf8String($value, $rowNum, $langKey);
                $config[$configKey] = $value;
            }
            $configs[] = [
                'key'     => $item['key'],
                'version' => $item['version'],
                'group'   => $group,
                'config'  => $config,
                'creator' => $item['creator'] ?? '',
                'updater' => $user,
            ];
        }
        $data = ['configs' => $configs];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_BATCH_UPDATE_CONFIG, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '批量修改失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return true;
    }

    public function modify($params)
    {
        $user = Helper::getSystemUserInfo()['user_name'] ?? '';
        if (empty($params['key'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'key不能为空');
        }
        if (empty($params['version'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'version不能为空');
        }
        if (!isset($params['group'])) {
            $params['group'] = 0;
        }
        if (!is_numeric($params['group'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'group必须是数字');
        }
        $group = (int)$params['group'];
        if (!array_key_exists($group, self::$groupMap)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group不合法');
        }
        
        // 获取修改前的记录
        list($getRes, $_) = $this->psRpc->call(PsRpc::API_I18N_GET_CONFIG, ['query' => ['key' => $params['key']]]);
        if (!isset($getRes['common']['err_code']) || $getRes['common']['err_code'] != 0 || empty($getRes['config'])) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }
        $beforeInfo = $getRes['config'];
        $id = $beforeInfo['id'];
        
        // 组装 config
        $config = [];
        foreach (self::$languageMp as $langKey => $configKey) {
            $value = $params[$langKey] ?? '';
            // 清理和验证UTF-8字符
            $value = $this->cleanUtf8String($value, 1, $langKey);
            $config[$configKey] = $value;
        }
        $data = [
            'data' => [
                'id'      => $id,
                'key'     => $params['key'],
                'version' => $params['version'],
                'group'   => $group,
                'config'  => $config,
                'creator' => $params['creator'] ?? '',
                'updater' => $user,
            ]
        ];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_UPDATE_CONFIG, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '修改失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $id, 'before_json' => $beforeInfo, 'after_json' => $data['data']];
    }

    public function delete($params)
    {
        $key = $params['key'] ?? '';
        if (!$key) {
            throw new ApiException(ApiException::MSG_ERROR, 'key不能为空');
        }
        
        // 获取删除前的记录
        list($getRes, $_) = $this->psRpc->call(PsRpc::API_I18N_GET_CONFIG, ['query' => ['key' => $key]]);
        if (!isset($getRes['common']['err_code']) || $getRes['common']['err_code'] != 0 || empty($getRes['config']['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }
        $info = $getRes['config'];
        
        $data = ['key' => $key];
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_DELETE_CONFIG, ['json' => $data]);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '删除失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => $info['id'], 'before_json' => $info];
    }

    public function publish()
    {
        list($res, $_) = $this->psRpc->call(PsRpc::API_I18N_PUBLISH);
        if (!isset($res['common']['err_code']) || $res['common']['err_code'] != 0) {
            $msg = $res['common']['err_msg'] ?? '发布失败';
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['id' => 0];
    }

    public function import(array $list)
    {
        return $this->batchCreate(['list' => $list]);
    }

    public function getGroupMap()
    {
        return StatusService::formatMap(self::$groupMap);
    }

    private function convertGroupValue($groupValue, $rowNum = null)
    {
        // 如果已经是数字，验证是否在有效范围内
        if (is_numeric($groupValue)) {
            $intValue = (int)$groupValue;
            if (!array_key_exists($intValue, self::$groupMap)) {
                $errorMsg = $rowNum ? "第{$rowNum}行：group值'{$groupValue}'不合法，支持的值为：0,1,2,3 或 客户端、服务端、web端、3端公用" : "group值'{$groupValue}'不合法";
                return ['value' => null, 'error' => $errorMsg];
            }
            return ['value' => $intValue, 'error' => null];
        }
        
        // 如果是字符串，尝试从 groupMap 中找到对应的 key
        $groupMap = array_flip(self::$groupMap);
        
        if (!array_key_exists($groupValue, $groupMap)) {
            $errorMsg = $rowNum ? "第{$rowNum}行：group值'{$groupValue}'不合法，支持的值为：客户端、服务端、web端、3端公用" : "group值'{$groupValue}'不合法";
            return ['value' => null, 'error' => $errorMsg];
        }

        return ['value' => $groupMap[$groupValue], 'error' => null];
    }



    /**
     * 清理和验证UTF-8字符串
     * @param string $value 输入字符串
     * @param int $rowNum 行号
     * @param string $langKey 语言键
     * @return string 清理后的字符串
     */
    private function cleanUtf8String(string $value, int $rowNum, string $langKey): string
    {
        if (empty($value)) {
            return $value;
        }
        
        // 检查是否为有效的UTF-8
        if (!mb_check_encoding($value, 'UTF-8')) {
            // 尝试修复UTF-8编码
            $fixedValue = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (!mb_check_encoding($fixedValue, 'UTF-8')) {
                // 如果修复失败，记录错误并返回空字符串
                error_log("UTF-8编码错误: 第{$rowNum}行 {$langKey} 字段包含损坏的UTF-8字符: " . bin2hex($value));
                return '';
            }
            $value = $fixedValue;
        }
        
        // 移除不可见字符和控制字符
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // 移除零宽字符
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        
        return $value;
    }
} 