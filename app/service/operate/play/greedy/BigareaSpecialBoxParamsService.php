<?php

namespace Imee\Service\Operate\Play\Greedy;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsGlobalConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class BigareaSpecialBoxParamsService
{
    public function getList(array $params): array
    {
        $bigAreaId = intval($params['bigarea_id'] ?? 3);
        $config = XsBigarea::findOne($bigAreaId)['greedy_box'] ?? [];
        if ($config) {
            $config = json_decode($config, true);
            $config = $config['special_box_config'] ?? [];
        }
        $ids = XsGlobalConfig::getLogId(array_values(XsBigarea::$boxConfigIds), $bigAreaId);
        $logs = BmsOperateLog::getFirstLogList('bigareaspecialboxconfig', $ids);
        $map = [];
        foreach (XsBigarea::$specailConfigIds as $key => $item) {
            $_id = XsGlobalConfig::getLogId($item, $bigAreaId);
            $tmp = [
                'id'         => $item,
                'bigarea_id' => $bigAreaId,
                'c_name'     => XsBigarea::$boxConfigAll[$key],
                'number'     => $config[$key] ?? 0,
                'admin_name' => $logs[$_id]['operate_name'] ?? '-',
                'dateline'   => isset($logs[$_id]['created_time']) ? Helper::now($logs[$_id]['created_time']) : '',
            ];
            $tmp['num_text'] = $tmp['number'];
            $map[] = $tmp;
        }

        return $map;
    }

    public function modify(array $params): array
    {
        $id = $params['id'] ?? 0;
        $bigAreaId = $params['bigarea_id'] ?? 0;
        $number = intval($params['number'] ?? 0);

        list($valid, $data) = $this->valid($id, $bigAreaId, $number);
        if (!$valid) {
            return [$valid, $data];
        }
        [$res, $msg] = (new PsService())->setBigAreaGreedyBox($bigAreaId, $data);
        if (!$res) {
            return [false, $msg];
        }
        return [true, ['id' => XsGlobalConfig::getLogId(intval($id), $bigAreaId), 'after_json' => [
            'id'         => $id,
            'number'     => $number,
            'bigarea_id' => $bigAreaId,
        ]]];
    }

    private function valid(int $id, int $bigAreaId, int $number): array
    {
        if (empty($id) || empty($bigAreaId)) {
            return [false, '参数配置错误'];
        }
        $config = XsBigarea::setGreedyBoxDefault($bigAreaId);
        $field = array_search($id, XsBigarea::$boxConfigIds);
        $config['special_box_config'][$field] = $number;
        // 前两项为默认配置不需要传
        unset($config['config']['greedy_box_diamond'][0], $config['config']['greedy_box_diamond'][1]);
        $config['config']['greedy_box_diamond'] = array_values($config['config']['greedy_box_diamond']);
        
        return [true, $config];
    }
}