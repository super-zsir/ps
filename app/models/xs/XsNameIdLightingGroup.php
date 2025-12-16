<?php

namespace Imee\Models\Xs;

class XsNameIdLightingGroup extends BaseModel
{
    protected static $primaryKey = 'id';

    public const SCHEMA_READ = 'xsserverslave';

    public static function formatName($name): string
    {
        if (!is_array($name)) {
            return '';
        }

        return isset($name['zh_cn'])?$name['zh_cn']:(isset($name['en'])?$name['en']:'');

//        $lan = XsBigarea::getLanguageArr();
//        $str = '';
//        foreach ($lan as $k => $v) {
//            if (!empty($name[$k])) {
//                $str .= $v . ':' . $name[$k] . '<br>';
//            }
//        }
//        return $str;
    }

    public static function getInfo(int $cid): array
    {
        return self::findOne($cid);
    }

}