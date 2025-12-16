<?php

namespace Imee\Helper\Traits;

/**
 * 位图运算
 */
trait BitmapTrait
{
    /**
     * 合并多个选项值为一个位图（如：1 | 2 | 4 = 7）
     * @param int[] $options
     * @return int
     */
    public static function merge(array $options): int
    {
        return array_reduce($options, function ($carry, $item) {
            return $carry | $item;
        }, 0);
    }

    /**
     * 判断某个选项是否已设置
     * @param int $bitmap
     * @param int $option
     * @return bool
     */
    public static function has(int $bitmap, int $option): bool
    {
        return ($bitmap & $option) !== 0;
    }

    /**
     * 添加一个选项
     * @param int $bitmap
     * @param int $option
     * @return int
     */
    public static function add(int $bitmap, int $option): int
    {
        return $bitmap | $option;
    }

    /**
     * 移除一个选项
     * @param int $bitmap
     * @param int $option
     * @return int
     */
    public static function remove(int $bitmap, int $option): int
    {
        return $bitmap & (~$option);
    }

    /**
     * 获取已设置的所有选项（返回 [值 => 描述]）
     * @param int $bitmap
     * @param array $optionMap [int => string]
     * @return array
     */
    public static function extract(int $bitmap, array $optionMap): array
    {
        $result = [];
        foreach ($optionMap as $value => $label) {
            if (($bitmap & $value) !== 0) {
                $result[$value] = $label;
            }
        }
        return $result;
    }

    /**
     * 获取所有选中的原始值（仅返回键）
     * @param int $bitmap
     * @return int[]
     */
    public static function getSetBits(int $bitmap): array
    {
        $bits = [];
        for ($i = 0; $i < 32; $i++) {
            if (($bitmap & (1 << $i)) !== 0) {
                $bits[] = 1 << $i;
            }
        }
        return $bits;
    }
}