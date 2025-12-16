<?php

namespace Imee\Comp\Ai;

class ModelScanner
{
    private $modelPath;

    public function __construct()
    {
        $this->modelPath = ROOT . '/app/models/xs/';
    }

    public function findModelByKeyword($keyword)
    {
        $files = glob($this->modelPath . 'Xs*.php');
        $bestMatch = null;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');
            
            if (stripos($className, $keyword) !== false) {
                return $this->parseModel($className, $content);
            }
            
            // 简单的注释匹配
            if (stripos($content, $keyword) !== false) {
                $bestMatch = $this->parseModel($className, $content);
            }
        }
        
        return $bestMatch;
    }

    private function parseModel($className, $content)
    {
        $fields = [];
        if (preg_match_all('/public\s+\$([a-zA-Z0-9_]+)/', $content, $matches)) {
            $fields = $matches[1];
        }

        // 过滤非数据库字段
        $fields = array_filter($fields, function($f) {
            return !in_array($f, ['primaryKey', 'vipLevelMap', 'vipDaysMap', 'deleted_arr', 'reasonArr', 'sex_arr', 'onlineStatusArr']);
        });

        return [
            'class' => $className,
            'namespace' => 'Imee\\Models\\Xs',
            'fields' => array_values($fields)
        ];
    }
}
