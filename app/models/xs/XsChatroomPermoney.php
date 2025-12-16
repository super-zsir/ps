<?php

namespace Imee\Models\Xs;

class XsChatroomPermoney extends BaseModel
{
    protected $primaryKey = 'id';

    const TYPE_FRIEND = 'friend';
    const TYPE_MUSIC = 'music';
    const TYPE_PIA = 'pia';
    const TYPE_TALK = 'talk';
    const TYPE_RADIO = 'radio';
    const TYPE_BILL = 'bill';
    const TYPE_DATE = 'date';
    const TYPE_FRIENDS = 'friends';

    public static $typeMap = [
        self::TYPE_FRIEND  => '交友',
        self::TYPE_MUSIC   => '点唱',
        self::TYPE_PIA     => 'PIA戏',
        self::TYPE_TALK    => '脱口秀',
        self::TYPE_RADIO   => '电台',
        self::TYPE_BILL    => '点单',
        self::TYPE_DATE    => '相亲',
        self::TYPE_FRIENDS => '谈恋爱',
    ];

    const STATE_WAIT_EDIT = 0;
    const STATE_ALREADY_EDIT = 1;
    const STATE_ALREADY_SEND = 2;

    public static $stateMap = [
        self::STATE_WAIT_EDIT    => '待修改',
        self::STATE_ALREADY_EDIT => '已修改',
        self::STATE_ALREADY_SEND => '已发送',
    ];
}