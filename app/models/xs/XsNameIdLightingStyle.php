<?php

namespace Imee\Models\Xs;

class XsNameIdLightingStyle extends BaseModel
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

    public static $directionMaps = [
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
    public static $multiColorStyleMaps = [
        self::MULTI_COLOR_STYLE_LINE => '线性渐变',
    ];


    public static $color = [
        ['#75FFC3', '#D0FF00', '#75FFC3'],
        ['#A392FF', '#FCA8FF', '#A392FF'],
        ['#ED7FFF', '#FF41D0', '#FF1F1F'],
        ['#A8FDFF', '#00E0FF', '#4CA6FF'],
        ['#FFCC11', '#FFFC00', '#FFCC11'],
        ['#FF5E00', '#FFDF00', '#FF5E00'],
        ['#FFD200', '#FF4E00', '#FF00DC'],
        ['#00FFCD', '#0021FF', '#FF00DC'],
        ['#FFFC00', '#3DFF86', '#0FFAFF'],
    ];

}