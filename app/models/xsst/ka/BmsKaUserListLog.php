<?php

namespace Imee\Models\Xsst\Ka;

use Imee\Models\Xsst\BaseModel;

class BmsKaUserListLog extends BaseModel
{
    protected $allowEmptyStringArr = ['field', 'field_from', 'field_to'];

    public static $_fieldComment = [
        'kf_id'           => '客服',
        'build_al_status' => '建联状态'
    ];

    /**
     * 保存日志
     * @param  BmsKaUserList  $old
     * @param  BmsKaUserList  $new
     * @param  array                $extra
     * @return bool
     */
    public static function saveLog(BmsKaUserList $old, BmsKaUserList $new, $extra = [])
    {
        // 规定记录日志的字段
        $changeFields = array_keys(self::$_fieldComment);

        $data = [];
        $now  = time();

        foreach ($changeFields as $changeField) {
            if ($old->{$changeField} === $new->{$changeField}) {
                continue;
            }

            $data[] = [
                'uid'         => $new->uid,
                'admin'       => $extra['admin'] ?? 0,
                'field'       => $changeField,
                'field_from'  => $old->{$changeField},
                'field_to'    => $new->{$changeField},
                'create_time' => $now,
                'source'      => $extra['source'] ?? '',
            ];
        }

        !empty($data) && self::addBatch($data);

        return true;
    }

    /**
     * 保存第一次创建的信息日志
     */
    public static function saveNewLog($data, $type = 'kf_id', $extra = [])
    {
        $now  = time();

        $rec = new self();

        $rec->uid = $data['uid'];
        $rec->admin = isset($extra['admin']) ? $extra['admin'] : 0;
        $rec->field = $type;
        $rec->field_from = 0;
        $rec->field_to = $data[$type];
        $rec->create_time = $now;
        $rec->source = isset($extra['source']) ? $extra['source'] : '';
        $rec->save();
    }
}
