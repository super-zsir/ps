<?php

namespace Imee\Models\Xs;

class XsCategory extends BaseModel
{
    protected static $primaryKey = 'cid';

    const XIE_HOU_PID = 194;

    public static $typeMap = [
        'all'     => '都有',
        'text'    => '图文',
        'online'  => '连麦',
        'video'   => '视频',
        'offline' => '线下',
    ];

    public static $useSkillCoverMap = [
        0 => '人物',
        1 => '技能',
    ];

    public static $displayMap = [
        0 => '送审不显示',
        1 => '送审显示',
    ];

    public static $serviceUidMap = [
        0         => '-- 为品类指定客服 -- ',
        100000001 => '官方考核（线上游戏）',
        100000002 => '官方考核（手游品类）',
        100000003 => '官方考核（线下游戏）',
        100000009 => '官方考核（线上歌手）',
        100000010 => '官方考核（线下玩乐01）',
        100000011 => '官方考核（线下玩乐02）',
        100000012 => '官方考核（视频聊天）',
        100000013 => '官方考核（声优聊天）',
    ];

    public static $videoNeedMap = [
        0 => '-上传视频-',
        1 => '需要视频'
    ];

    public static $audioNeedMap = [
        0 => '-上传音频-',
        1 => '需要音频'
    ];

    public static $deletedMap = [
        0 => '正常',
        1 => '删除'
    ];
}