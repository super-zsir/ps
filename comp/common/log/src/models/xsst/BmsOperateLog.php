<?php
/**
 * 后台操作日志记录
 */

namespace Imee\Comp\Common\Log\Models\Xsst;

use Imee\Helper\Constant\LogConstant;

class BmsOperateLog extends BaseModel
{
    protected static $primaryKey = 'id';
    protected $allowEmptyStringArr = ['before_json', 'after_json'];

    const TYPE_OPERATE_LOG = 0;//运营后台操作日志
    const TYPE_CLI_LOG = 1;

    const ACTION_ADD = 0;//添加
    const ACTION_UPDATE = 1;//修改
    const ACTION_DEL = 2;//删除
    const ACTION_REVIEW = 3;//审核

    public static $modelMapping = [];

    public static function getModelMapping(): array
    {
        if (class_exists('Imee\Helper\Constant\LogConstant') && defined('Imee\Helper\Constant\LogConstant::LOG_MODEL_MAP')) {
            return LogConstant::LOG_MODEL_MAP;
        }

        return self::$modelMapping;
    }

    /**
     * 获取最近的一条操作日志
     * @param $model
     * @param $modelId
     * @return array
     */
    public static function getFirstLogList($model, $modelId): array
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
        $data = self::getListByWhere($condition, 'model_id,operate_id,operate_name,created_time', 'id desc');

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['model_id']])) {
                continue;
            }
            $res[$val['model_id']] = $val;
        }

        return $res;
    }

    /**
     * 获取指定操作类型的最近一条日志
     * @param $model
     * @param $modelId
     * @param $action
     * @return array
     */
    public static function getFirstLogListByAction($model, $modelId, $action): array
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
        $data = self::getListByWhere($condition, 'model_id,operate_id,operate_name,created_time', 'id desc');

        $res = [];
        foreach ($data as $val) {
            if (!empty($res[$val['model_id']])) {
                continue;
            }
            $res[$val['model_id']] = $val;
        }

        return $res;
    }

    // 返回表名 todo
    public function getSource()
    {
        return 'bms_operate_log';
    }
}
