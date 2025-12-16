<?php

namespace Imee\Models\Lemon;

class UserPopularityLevel extends BaseModel
{
    private static $_levels = array(
        252000000,
        182000000,
        127000000,
        82000000,
        52000000,
        32000000,
        17000000,
        7000000,
        2000000,
        0,
    );

    public static function getLevel(int $point): int
    {
        $level = $len = count(self::$_levels);
        if ($point < self::$_levels[$len - 1]) return 0;
        for ($i = 0; $i < $len; $i++) {
            if ($point >= self::$_levels[$i]) {
                break;
            }
            $level--;
        }
        return $level;
    }
}