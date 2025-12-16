<?php

namespace Imee\Models\Xss;

class CsmsChoice extends BaseModel
{
    const JOIINUP_BINLOG = 'binlog';
    const JOIINUP_NSQ = 'nsq';
    const JOIINUP_KAFKA = 'kafka';
    const JOIINUP_RPC = 'rpc';


    public static $joinup = [
        self::JOIINUP_BINLOG => 'binlog',
        self::JOIINUP_NSQ    => 'nsq',
        self::JOIINUP_KAFKA  => 'kafka',
        self::JOIINUP_RPC    => 'rpc',
    ];


    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';
    const TYPE_MIXTURE = 'mixture';


    public static $choice_type = [
        self::TYPE_TEXT    => '文本',
        self::TYPE_IMAGE   => '图片',
        self::TYPE_AUDIO   => '音频',
        self::TYPE_VIDEO   => '视频',
        self::TYPE_MIXTURE => '混合型',
    ];

    const state_normal = 1; // 正常
    const state_unnormal = 2; // 下线

    const state = [
        1 => '正常',
        2 => '下线'
    ];

    /**
     * @param array $condition
     * @return \Phalcon\Mvc\Model\Query\Builder
     */
    public static function queryBuilder(array $condition = [])
    {
        $query = static::baseQueryBuilder($condition);
        foreach ($condition as $key => $value) {
            switch ($key) {
                case 'id':
                    $query->andWhere('id = :id:', ['id' => $value]);
                    break;
                case 'id_array':
                    $query->inWhere('id', $value);
                    break;
                case 'choice':
                    is_array($value) ? $query->inWhere('choice', $value) :
                        $query->andWhere('choice = :choice:', ['choice' => $value]);
                    break;
                case 'state':
                    $query->andWhere('state = :state:', ['state' => $value]);
                    break;
                case 'groupBy':
                    $query->groupBy($value);
                    break;
                case 'orderBy':
                    $query->orderBy($value);
                    break;
                case 'limit':
                    $query->limit($value);
                    break;
                case 'offset':
                    $query->offset($value);
                    break;
                case 'having':
                    $query->having($value);
                    break;
                case 'columns':
                    // 查询的字段
                    $query->columns($value);
                    break;
                default:
                    break;
            }
        }
        return $query;
    }
}
