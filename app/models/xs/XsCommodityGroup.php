<?php
/**
 * 物品分组表
 */

namespace Imee\Models\Xs;

class XsCommodityGroup extends BaseModel
{
    protected static $primaryKey = 'group_id';

    protected $allowEmptyStringArr = [
    ];

    public static $nameBigarea = [
        'group_name'       => '中文大区名称',
        'group_name_zh_tw' => '台湾大区名称',
        'group_name_en'    => '英文大区名称',
        'group_name_ar'    => '阿语大区名称',
        'group_name_ms'    => '马来大区名称',
        'group_name_th'    => '泰语大区名称',
        'group_name_id'    => '印尼大区名称',
        'group_name_vi'    => '越南大区名称',
        'group_name_ko'    => '韩语大区名称',
        'group_name_tr'    => '土耳其大区名称',
        'group_name_ja'    => '日语大区名称',
        'group_name_hi'    => '印地语版名称',
        'group_name_bn'    => '孟加拉语版名称',
        'group_name_ur'    => '乌尔都语版名称',
        'group_name_tl'    => '他加禄语版名称',
    ];

    public static function getListByGroupName($appId, $groupName)
    {
        $model = self::query();

        $bindValue = "%{$groupName}%";
        $model->andWhere("group_name LIKE :group_name:", ['group_name' => $bindValue]);
        $model->andWhere("app_id = :app_id:", ['app_id' => $appId]);

        return $model->execute()->toArray();
    }
}
