<?php

namespace Imee\Models\Config;

class BbcChatroomModuleTag extends BaseModel
{
    protected $primaryKey = 'id';

    /**
     * 获取标签枚举
     * @return array
     */
    public static function getOptions(): array
    {
        $map = [];

        $tagList = self::findAll();

        foreach ($tagList as &$tag) {
            if ($tag['show'] != $tag['label'] && false === stripos($tag['show'], $tag['label'])) {
                $tag['show'] .= "({$tag['label']})";
            }
            $map[$tag['id']] = $tag['show'];
        }

        return $map;
    }
}