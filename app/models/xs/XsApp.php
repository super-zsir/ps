<?php


namespace Imee\Models\Xs;


class XsApp extends BaseModel
{
    // 安装包二维码 暂不设置
    private static  $_packageName = array(
//        'com.imbb.banban.android' => APP_BANBAN,  //伴伴安卓
    );

    public static function getAllPkg()
    {
        return array_keys(self::$_packageName);
    }


}