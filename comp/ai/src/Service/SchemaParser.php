<?php

namespace Imee\Comp\Ai\Service;

class SchemaParser
{
    /**
     * 解析前端 Schema JSON，提取后端 API 需求
     * @param string $jsonContent
     * @return array
     */
    public function parse(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);
        if (!$data) {
            return [];
        }

        $tasks = [];
        $zones = $data['zones']['root:root'] ?? [];
        $propsMap = $data['zones'] ?? []; // 其他区域的组件属性

        // 1. 扫描主区域
        foreach ($zones as $component) {
            $this->extractTasksFromComponent($component, $tasks, $propsMap);
        }

        return $tasks;
    }

    private function extractTasksFromComponent($component, &$tasks, $propsMap)
    {
        $type = $component['type'] ?? '';
        $props = $component['props'] ?? [];
        $componentId = $component['props']['id'] ?? '';

        // 1. 处理 SearchTable (列表查询)
        if ($type === 'SearchTable') {
            if (isset($props['apiConfig']['requirementDescription'])) {
                $tasks[] = [
                    'type' => 'list',
                    'desc' => $props['apiConfig']['requirementDescription'],
                    'model' => $this->extractModelName($props['apiConfig']['requirementDescription']),
                    'columns' => array_column($props['columns'] ?? [], 'dataIndex'),
                    'api_url' => $props['apiConfig']['apiUrl'] ?? ''
                ];
            }

            // 处理顶部按钮 (TopActions)
            if (!empty($props['topActions']['customButtons'])) {
                foreach ($props['topActions']['customButtons'] as $btn) {
                    $this->extractApiFromButton($btn, $tasks);
                }
            }

            // 处理列操作按钮 (ColumnActions)
            if (!empty($props['columnActions']['customButtons'])) {
                foreach ($props['columnActions']['customButtons'] as $btn) {
                    $this->extractApiFromButton($btn, $tasks);
                }
            }
        }

        // 2. 处理 FormModal (增/改/查详情)
        if ($type === 'FormModal') {
            $apiConfig = $props['apiConfig'] ?? [];
            
            // 新增
            if (isset($apiConfig['addRequirementDescription'])) {
                $tasks[] = [
                    'type' => 'create',
                    'desc' => $apiConfig['addRequirementDescription'],
                    'model' => $this->extractModelName($apiConfig['addRequirementDescription']),
                    'fields' => $this->extractFieldsFromModalItems($componentId, $propsMap),
                    'api_url' => $apiConfig['addApiUrl'] ?? ''
                ];
            }

            // 修改
            if (isset($apiConfig['submitRequirementDescription'])) {
                $tasks[] = [
                    'type' => 'modify',
                    'desc' => $apiConfig['submitRequirementDescription'],
                    'model' => $this->extractModelName($apiConfig['submitRequirementDescription']),
                    'fields' => $this->extractFieldsFromModalItems($componentId, $propsMap),
                    'api_url' => $apiConfig['submitApiUrl'] ?? ''
                ];
            }

            // 详情
            if (isset($apiConfig['detailRequirementDescription'])) {
                $tasks[] = [
                    'type' => 'detail',
                    'desc' => $apiConfig['detailRequirementDescription'],
                    'model' => $this->extractModelName($apiConfig['detailRequirementDescription']),
                    'api_url' => $apiConfig['detailApiUrl'] ?? ''
                ];
            }
        }
    }

    private function extractApiFromButton($btn, &$tasks)
    {
        if (isset($btn['api']['description'])) {
            $desc = $btn['api']['description'];
            $tasks[] = [
                'type' => 'custom', // 自定义操作
                'desc' => $desc,
                'model' => $this->extractModelName($desc),
                'action' => $btn['api']['action'] ?? 'unknown',
                'api_url' => $btn['api']['url'] ?? ''
            ];
        }
    }

    private function extractFieldsFromModalItems($modalId, $propsMap)
    {
        $itemsKey = $modalId . ':modal-items';
        if (!isset($propsMap[$itemsKey])) {
            return [];
        }
        
        $fields = [];
        foreach ($propsMap[$itemsKey] as $item) {
            if (isset($item['props']['name'])) {
                $fields[] = $item['props']['name'];
            }
        }
        return $fields;
    }

    private function extractModelName($text)
    {
        if (preg_match('/Model：([a-zA-Z0-9_]+)/', $text, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
