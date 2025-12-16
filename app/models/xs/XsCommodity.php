<?php
/**
 * 物品表
 */

namespace Imee\Models\Xs;

class XsCommodity extends BaseModel
{
    protected static $primaryKey = 'cid';

    protected $allowEmptyStringArr = [
        'ext_name', 'jump_page', 'image', 'name_zh_tw', 'name_en', 'ext_id_more',
        'name_ar', 'name_ms', 'name_th', 'name_id', 'name_vi', 'name_ko', 'name_jp', 'name_tr',
        'tag_ids', 'description', 'image_bg', 'sub_type', 'extra', 'name_ja', 'name_pt', 'name_es'
    ];

    public $title = 0;

    public static function getCommodityList()
    {
        $commoditys = self::getListByWhere([], 'cid,name', 'cid desc');
        if (empty($commoditys)) {
            return [];
        }

        $data = [];

        foreach ($commoditys as $commodity) {
            $data[$commodity['cid']] = $commodity['cid'] . '_' . $commodity['name'];
        }

        return $data;
    }
}
