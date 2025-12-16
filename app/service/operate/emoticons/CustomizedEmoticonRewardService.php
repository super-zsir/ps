<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;
use Imee\Service\Helper;
use Imee\Service\StatusService;

class CustomizedEmoticonRewardService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 获取表情发放记录列表
     * @param array $params
     * @return array
     */
    public function getList(array $params): array
    {
        $queryData = [];
        
        if (!empty($params['uid'])) {
            $queryData['uid'] = (int)$params['uid'];
        }
        if (!empty($params['big_area_id'])) {
            $queryData['big_area_id'] = (int)$params['big_area_id'];
        }
        if (!empty($params['start_time'])) {
            $queryData['start_time'] = strtotime($params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $queryData['end_time'] = strtotime($params['end_time']);
        }
        if (!empty($params['customized_emoticon_id'])) {
            $queryData['customized_emoticon_id'] = (int)$params['customized_emoticon_id'];
        }
        if (!empty($params['status'])) {
            $queryData['status'] = (int)$params['status'];
        }
        if (!empty($params['limit'])) {
            $queryData['page_size'] = (int)$params['limit'];
        } else {
            $queryData['page_size'] = 15; // 默认每页15条
        }
        if (!empty($params['page'])) {
            $queryData['page_num'] = (int)$params['page'];
        } else {
            $queryData['page_num'] = 1; // 默认第1页
        }

        list($res, $msg, $data) = $this->rpcService->queryCustomizedEmoticonReward($queryData);

        if (!$res) {
            // 开发环境使用 mock 数据
            if (ENV == 'dev') {
                $mockResult = $this->getMockData($queryData);
                return [
                    'data' => $this->formatList($mockResult['list'] ?? []),
                    'total' => $mockResult['total'] ?? 0
                ];
            }
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return [
            'data' => $this->formatList($data['list'] ?? []),
            'total' => $data['total'] ?? 0
        ];
    }

    /**
     * 发放表情
     * @param array $params
     * @return array
     */
    public function create(array $params): array
    {
        // 处理逗号分隔的uid
        $uids = $this->parseUids($params['uid']);
        
        // 构建info_list数组
        $infoList = [];
        foreach ($uids as $uid) {
            $infoList[] = [
                'uid' => (int)$uid,
                'customized_emoticon_id' => (int)$params['customized_emoticon_id'],
                'valid_day' => (int)$params['valid_day'],
                'reason' => $params['reason'] ?? ''
            ];
        }
        
        $createData = [
            'info_list' => $infoList,
            'operator' => Helper::getSystemUserInfo()['user_name'] ?? ''
        ];

        list($res, $msg) = $this->rpcService->createCustomizedEmoticonReward($createData);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $createData['info_list']];
    }

    /**
     * 解析uid字符串，支持逗号分隔的多个uid
     * @param string $uidString
     * @return array
     */
    private function parseUids(string $uidString): array
    {
        // 去除空格并按逗号分割
        $uids = array_map('trim', explode(',', $uidString));
        
        // 过滤空值和无效的uid
        $validUids = [];
        foreach ($uids as $uid) {
            if (!empty($uid) && is_numeric($uid) && (int)$uid > 0) {
                $validUids[] = (int)$uid;
            }
        }
        
        if (empty($validUids)) {
            throw new ApiException(ApiException::MSG_ERROR, 'UID格式不正确，请提供有效的UID');
        }
        
        // 去重
        return array_unique($validUids);
    }

    /**
     * 批量发放表情
     * @param array $data 二维数组，每行包含 uid, customized_emoticon_id, valid_day, reason
     * @return array
     */
    public function batchCreate(array $data): array
    {
        if (empty($data)) {
            throw new ApiException(ApiException::MSG_ERROR, '数据不能为空');
        }

        // 智能过滤表头，检查第一条记录是否为表头
        $filteredData = $this->filterTableHeader($data);
        
        if (empty($filteredData)) {
            throw new ApiException(ApiException::MSG_ERROR, '数据为空');
        }

        if (count($filteredData) > 200) {
            throw new ApiException(ApiException::MSG_ERROR, '批量发放最多支持200个用户');
        }

        // 构建批量请求数据
        $infoList = [];
        foreach ($filteredData as $row) {
            // 验证必填字段
            if (empty($row['uid']) || empty($row['customized_emoticon_id']) || empty($row['valid_day']) || empty($row['reason'])) {
                throw new ApiException(ApiException::MSG_ERROR, 'CSV数据中存在必填字段缺失的行');
            }
            
            // 验证uid是否为整数且大于0
            if (!is_numeric($row['uid']) || (int)$row['uid'] <= 0) {
                throw new ApiException(ApiException::MSG_ERROR, 'CSV数据中存在无效的UID，必须是大于0的整数');
            }
            
            // 验证customized_emoticon_id是否为整数且大于0
            if (!is_numeric($row['customized_emoticon_id']) || (int)$row['customized_emoticon_id'] <= 0) {
                throw new ApiException(ApiException::MSG_ERROR, 'CSV数据中存在无效的表情ID，必须是大于0的整数');
            }
            
            // 验证valid_day是否为整数且大于0
            if (!is_numeric($row['valid_day']) || (int)$row['valid_day'] <= 0) {
                throw new ApiException(ApiException::MSG_ERROR, 'CSV数据中存在无效的生效时间，必须是大于0的整数');
            }
            
            $infoList[] = [
                'uid' => (int)$row['uid'],
                'customized_emoticon_id' => (int)$row['customized_emoticon_id'],
                'valid_day' => (int)$row['valid_day'],
                'reason' => $row['reason'] ?? ''
            ];
        }

        $createData = [
            'info_list' => $infoList,
            'operator' => Helper::getSystemUserInfo()['user_name'] ?? ''
        ];

        // 调用RPC接口进行批量创建
        list($res, $msg) = $this->rpcService->createCustomizedEmoticonReward($createData);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => 0, 'after_json' => $infoList];
    }

    /**
     * 失效表情
     * @param array $params
     * @return array
     */
    public function deactivate(array $params): array
    {
        if (empty($params['id'])) {
            throw new ApiException(ApiException::MSG_ERROR, 'ID不能为空');
        }

        list($res, $msg) = $this->rpcService->deactivateCustomizedEmoticonReward([
            'id' => (int)$params['id']
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $params['id'], 'status' => 'deactivated'];
    }

    /**
     * 获取详情
     * @param int $id
     * @return array
     */
    public function info(int $id): array
    {
        $queryData = [
            'id' => $id,
            'page_size' => 1,
            'page_num' => 1
        ];

        list($res, $msg, $data) = $this->rpcService->queryCustomizedEmoticonReward($queryData);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        $list = $data['list'] ?? [];
        if (empty($list)) {
            throw new ApiException(ApiException::MSG_ERROR, '记录不存在');
        }

        return $this->formatDetail($list[0]);
    }

    public function getEmoticon(int $id): array
    {
        $params = [ 
            'id' => $id,
            'limit' => 1,
            'page' => 1
        ];
        $data = (new CustomizedEmoticonService())->getList($params);

        if (empty($data['data'])) {
            return [];
        }
        return StatusService::formatMap(array_column($data['data'], 'name_show', 'id'));
    }

    /**
     * 解析CSV数据
     * @param string $csvData
     * @return array
     * @throws ApiException
     */
    private function parseCsvData(string $csvData): array
    {
        $lines = explode("\n", trim($csvData));
        if (empty($lines)) {
            throw new ApiException(ApiException::MSG_ERROR, 'CSV数据为空');
        }
        
        $headers = str_getcsv(array_shift($lines));
        if (empty($headers)) {
            throw new ApiException(ApiException::MSG_ERROR, 'CSV表头为空');
        }
        
        // 验证必需的列是否存在
        $requiredColumns = ['uid', 'customized_emoticon_id', 'valid_day'];
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                $missingColumns[] = $column;
            }
        }
        
        if (!empty($missingColumns)) {
            throw new ApiException(ApiException::MSG_ERROR, 'CSV缺少必需的列：' . implode(', ', $missingColumns));
        }
        
        $result = [];
        foreach ($lines as $lineNum => $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line);
            if (count($row) >= count($headers)) {
                $item = [];
                foreach ($headers as $index => $header) {
                    $item[trim($header)] = trim($row[$index] ?? '');
                }
                $result[] = $item;
            } else {
                throw new ApiException(ApiException::MSG_ERROR, "CSV第" . ($lineNum + 2) . "行数据列数不足，期望" . count($headers) . "列，实际" . count($row) . "列");
            }
        }
        
        if (empty($result)) {
            throw new ApiException(ApiException::MSG_ERROR, 'CSV数据行数为空');
        }
        
        return $result;
    }

    /**
     * 格式化列表数据
     * @param array $list
     * @return array
     */
    private function formatList(array $list): array
    {
        $formatted = [];
        foreach ($list as $item) {
            $formatted[] = $this->formatDetail($item);
        }
        return $formatted;
    }

    /**
     * 将秒数转换为可读的时间格式
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0分钟';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $result = '';
        
        if ($days > 0) {
            $result .= $days . '天';
        }
        
        if ($hours > 0) {
            $result .= $hours . '小时';
        }
        
        if ($minutes > 0 || ($days == 0 && $hours == 0)) {
            $result .= $minutes . '分钟';
        }

        return $result;
    }

    /**
     * 格式化详情数据
     * @param array $item
     * @return array
     */
    private function formatDetail(array $item): array
    {
        return [
            'id' => $item['id'] ?? 0,
            'customized_emoticon_id' => $item['customized_emoticon_id'] ?? 0,
            'customized_emoticon_name' => $item['customized_emoticon_name'] ?? '',
            'uid' => $item['uid'] ?? 0,
            'name' => $item['name'] ?? '',
            'big_area_id' => $item['big_area_id'] ?? 0,
            'status' => $item['status'] ?? 0,
            'duration' => $this->formatDuration($item['duration'] ?? 0),
            'expire_time' => $item['expire_time'] ? date('Y-m-d H:i:s', $item['expire_time']) : '',
            'reason' => $item['reason'] ?? '',
            'operator' => $item['operator'] ?? '',
            'update_time' => $item['update_time'] ? date('Y-m-d H:i:s', $item['update_time']) : ''
        ];
    }

    /**
     * 获取Mock数据（开发环境使用）
     * @param array $queryData
     * @return array
     */
    private function getMockData(array $queryData): array
    {
        $mockData = [
            [
                'id' => 1323,
                'customized_emoticon_id' => 123134,
                'customized_emoticon_name' => '写歌燕舞',
                'uid' => 123134,
                'name' => '坤坤',
                'big_area_id' => 1,
                'status' => 1,
                'duration' => 7 * 24 * 3600,
                'expire_time' => time() + 7 * 24 * 3600,
                'reason' => '8558',
                'operator' => '白龙',
                'update_time' => time()
            ],
            [
                'id' => 1324,
                'customized_emoticon_id' => 123134,
                'customized_emoticon_name' => '写歌燕舞',
                'uid' => 123134,
                'name' => '坤坤',
                'big_area_id' => 1,
                'status' => 2,
                'duration' => 7 * 24 * 3600 + 3600,
                'expire_time' => time() + 7 * 24 * 3600 + 3600,
                'reason' => 'SSSS',
                'operator' => '白龙',
                'update_time' => time()
            ]
        ];

        return [
            'list' => $mockData,
            'total' => count($mockData)
        ];
    }

    /**
     * 智能过滤CSV表头
     * @param array $data
     * @return array
     */
    private function filterTableHeader(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $firstRow = current($data);
        
        // 直接判断第一行的uid是否为大于0的整数
        $isHeader = !(isset($firstRow['uid']) && is_numeric($firstRow['uid']) && (int)$firstRow['uid'] > 0);

        // 如果第一行是标题行，则过滤掉
        if ($isHeader) {
            return array_slice($data, 1);
        }

        // 如果第一行不是标题行，则返回所有数据
        return $data;
    }

    public function getStatusMap(): array
    {
        $statusMap = [
            1 => '有效',
            2 => '已失效'
        ];
        return StatusService::formatMap($statusMap);
    }

    public function getEmotionMap(): array
    {
        $data = (new CustomizedEmoticonService())->getList([
            'limit' => 10000,
            'page' => 1
        ]);
        $emotionMap = array_column($data['data'], 'name_show', 'id');
        return StatusService::formatMap($emotionMap);
    }
} 