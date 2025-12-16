<?php

namespace Imee\Models\Xsst;


use Imee\Models\Config\BbcOnepkObject;
use Imee\Service\Helper;

class XsstOnepkObjectLog extends BaseModel
{
    protected static $primaryKey = 'id';

    const TYPE_PUB = 1;
    const TYPE_UP = 2;
    const TYPE_DEL = 3;

    public static $typeMap = [
        self::TYPE_PUB => '发布',
        self::TYPE_UP  => '更新',
        self::TYPE_DEL => '删除'
    ];

    /**
     * 添加对战信息日志
     * @param int $id
     * @param int $type
     * @return array
     */
    public static function addOnepkRecord(int $id, int $type): array
    {
        $list = BbcOnepkObject::useMaster()::getListByWhere([
            ['act_id', '=', $id]
        ]);

        $data = [
            'admin_uid'   => Helper::getSystemUid(),
            'dateline'    => time(),
            'type'        => $type,
            'pk_obj_json' => json_encode($list),
            'aid'         => $id
        ];

        return self::add($data);
    }
}