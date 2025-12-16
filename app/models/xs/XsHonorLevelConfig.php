<?php

namespace Imee\Models\Xs;

class XsHonorLevelConfig extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    const LIGHTING_DIRECTION_LEFT_TO_RIGHT = 0;
    const LIGHTING_DIRECTION_RIGHT_TO_LEFT = 1;
    const LIGHTING_DIRECTION_TOP_TO_BOTTOM = 2;
    const LIGHTING_DIRECTION_BOTTOM_TO_TOP = 3;
    const LIGHTING_DIRECTION_LEFT_TOP_TO_RIGHT_BOTTOM = 4;
    const LIGHTING_DIRECTION_RIGHT_TOP_TO_LEFT_BOTTOM = 5;
    const LIGHTING_DIRECTION_LEFT_BOTTOM_TO_RIGHT_TOP = 6;
    const LIGHTING_DIRECTION_RIGHT_BOTTOM_TO_LEFT_TOP = 7;

    public static $shadeDirectionMaps = [
        self::LIGHTING_DIRECTION_LEFT_TO_RIGHT => '从左到右',
        self::LIGHTING_DIRECTION_RIGHT_TO_LEFT => '从右到左',
        self::LIGHTING_DIRECTION_TOP_TO_BOTTOM => '从上到下',
        self::LIGHTING_DIRECTION_BOTTOM_TO_TOP => '从下到上',
        self::LIGHTING_DIRECTION_LEFT_TOP_TO_RIGHT_BOTTOM => '从左上到右下',
        self::LIGHTING_DIRECTION_RIGHT_TOP_TO_LEFT_BOTTOM => '右上到左下',
        self::LIGHTING_DIRECTION_LEFT_BOTTOM_TO_RIGHT_TOP => '左下到右上',
        self::LIGHTING_DIRECTION_RIGHT_BOTTOM_TO_LEFT_TOP => '右下到左上',
    ];

    const MULTI_COLOR_STYLE_LINE = 0;
    public static $shadeStyleMaps = [
        self::MULTI_COLOR_STYLE_LINE => '线性渐变',
    ];

}