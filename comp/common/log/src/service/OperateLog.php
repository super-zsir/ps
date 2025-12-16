<?php
/**
 * 操作日志记录到db
 */

namespace Imee\Comp\Common\Log\Service;

use Imee\Comp\Common\Log\Models\Xsst\BmsOperateLog;

class OperateLog
{
    public static function getListAndTotal(array $filter, string $order, int $page = 0, int $limit = 0): array
    {
        $conditions = self::getConditions($filter);
        $res = BmsOperateLog::getListAndTotal($conditions, '*', $order, $page, $limit);
        if (!$res['data']) {
            return $res;
        }
        foreach ($res['data'] as &$val) {
            self::formatModal($val);
            $val['created_time'] = date('Y-m-d H:i:s', $val['created_time']);
            $val['operate_name'] && $val['operate_name'] = $val['operate_id'] . '-' . $val['operate_name'];
        }

        return $res;
    }

    private static function getConditions(array $filter): array
    {
        $conditions = [];
        if (isset($filter['type'])) {
            $conditions[] = ['type', '=', $filter['type']];
        }
        if (isset($filter['action'])) {
            $conditions[] = ['action', '=', $filter['action']];
        }
        if (!empty($filter['uid'])) {
            $conditions[] = ['uid', '=', $filter['uid']];
        }
        if (!empty($filter['model'])) {
            $conditions[] = ['model', '=', $filter['model']];
        }
        if (!empty($filter['created_time_sdate'])) {
            $conditions[] = ['created_time', '>=', strtotime($filter['created_time_sdate'])];
        }
        if (!empty($filter['created_time_edate'])) {
            $conditions[] = ['created_time', '<', strtotime($filter['created_time_edate']) + 86399];
        }
        if (!empty($filter['model_id'])) {
            if (!is_array($filter['model_id'])) {
                $filter['model_id'] = [$filter['model_id']];
            }
            $conditions[] = ['model_id', 'in', $filter['model_id']];
        }
        if (!empty($filter['operate_name'])) {
            $conditions[] = ['operate_name', 'like', $filter['operate_name']];
        }
        return $conditions;
    }

    private static function formatModal(&$val)
    {
        $val['json'] = [
            'title'    => '详情',
            'value'    => '修改详情',
            'type'     => 'manMadeModal',
            'modal_id' => 'log_modal',
            'params'   => [
                'id'          => $val['id'],
                'before_json' => self::jsonDecode($val['before_json']),
                'after_json'  => self::jsonDecode($val['after_json'])
            ]
        ];
    }

    public static function getFirstLogListMapping($model, $modelIds): array
    {
        return BmsOperateLog::getFirstLogList($model, $modelIds);
    }

    // 获取第一条记录
    public static function getFirstLog($modelId, $model): array
    {
        $conditions = [];
        $conditions[] = ['model', '=', $model];
        $conditions[] = ['model_id', '=', $modelId];
        return BmsOperateLog::getListByWhere($conditions, '*', 'id desc', 1)[0] ?? [];
    }

    public static function addLog($logRecordInfo, $request = [], $response = [])
    {
        $params = $request;
        if (!empty($response['data']) && is_array($response['data'])) {
            $params = array_merge($params, $response['data']);
        } else {
            $params['before_json'] = [];
            $params['after_json'] = $request;
        }
        $params['model'] = $logRecordInfo['model'];
        $params['model_id'] = $params[$logRecordInfo['model_id']] ?? ($response[$logRecordInfo['model_id']] ?? 0);
        $params['action'] = $logRecordInfo['action'];
        $params['content'] = $logRecordInfo['content'];
        $params['type'] = $logRecordInfo['type'] ?? 0;

        if (is_array($params['model_id'])) {
            $modelIds = $params['model_id'];
        } elseif (strstr($params['model_id'], ',')) {
            $modelIds = explode(',', $params['model_id']);
        }

        //兼容多个id批量处理
        if (!empty($modelIds)) {
            $insertBatch = [];
            foreach ($modelIds as $modelId) {
                $params['model_id'] = (int)$modelId;
                $insertBatch[] = self::packData($params);
            }
            return BmsOperateLog::addBatch($insertBatch);
        }

        return BmsOperateLog::add(self::packData($params));
    }

    public static function addOperateLog($params): array
    {
        return BmsOperateLog::add(self::packData($params));
    }

    public static function addOperateBatchLog($params): array
    {
        $inserts = [];
        foreach ($params as $param) {
            $inserts[] = self::packData($param);
        }
        return BmsOperateLog::addBatch($inserts);
    }

    private static function packData($params): array
    {
        if (isset($params['before_json'])) {
            $params['before_json'] = self::jsonEncode($params['before_json']);
        }
        if (isset($params['after_json'])) {
            $params['after_json'] = self::jsonEncode($params['after_json']);
        }
        return [
            'uid'          => $params['uid'] ?? 0,
            'model_id'     => $params['model_id'],
            'model'        => $params['model'],
            'action'       => $params['action'],
            'type'         => $params['type'] ?? 0,
            'content'      => $params['content'] ?? '',
            'before_json'  => $params['before_json'] ?? '',
            'after_json'   => $params['after_json'] ?? '',
            'operate_id'   => $params['operate_id'] ?? ($params['admin_id'] ?? 0),
            'operate_name' => $params['operate_name'] ?? '',
            'created_time' => time(),
        ];
    }

    private static function jsonEncode($data)
    {
        if (!$data) {
            return '';
        }
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $data;
    }

    private static function jsonDecode($data)
    {
        if (!$data) {
            return [];
        }
        return json_decode($data, true);
    }

    public static function getModelMap(): array
    {
        $map = BmsOperateLog::getModelMapping();

        $data = [];
        foreach ($map as $key => $val) {
            $data[] = [
                'label' => $val,
                'value' => $key
            ];
        }

        return $data;
    }

    /**
     * 获取指定操作类型的最近|最早一条日志
     * @param $model
     * @param $modelId
     * @param $action
     * @param string $order
     * @return array
     */
    public static function getFirstLogListByAction($model, $modelId, $action, string $order = 'id desc'): array
    {
        if (!$modelId || !$model) {
            return [];
        }

        if (!is_array($modelId)) {
            $modelId = [$modelId];
        }

        $condition = [];
        $condition[] = ['model', '=', $model];
        $condition[] = ['model_id', 'in', $modelId];
        $condition[] = ['action', '=', $action];
        $data = BmsOperateLog::getListByWhere($condition, 'model_id,operate_id,operate_name,created_time', $order);

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['model_id']])) {
                continue;
            }
            $res[$val['model_id']] = $val;
        }

        return $res;
    }
}