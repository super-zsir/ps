<?php

namespace Imee\Models\Xs;

class XsBanner extends BaseModel
{
    protected $allowEmptyStringArr = ['icon', 'url'];

    const DELETED_NO = 0;
    const DELETED_YES = 1;

    //默认分享图
    const SHARE_ICON = OSS_IMAGE_URL_WEB . '/logo.png';
    
    //聊天室banner
    public static $positionMapping = [
//        'banner'   => '活动banner',
        'chatroom' => '房间内广告',
        'live'     => '首页banner',
    ];

    //视频banner
    public static $positionLiveMapping = [
        'videoroom' => '直播间广告',
        'videofeed' => 'feed banner',
    ];

    //类型
    public static $typeMapping = [
//        'app'     => 'app内部页',
        'web'     => 'web页',
        'webview' => '内嵌h5',
    ];

    //平台
    public static $stypeMapping = [
        'app'  => '移动端',
//        'pc'   => 'pc端',
//        'test' => '测试用',
    ];

    //角色
    public static $roleMapping = [
        0 => '全部',
        1 => '主播/公会长',
        2 => '非主播',
        3 => '特定用户',
    ];

    //feed展示位置
    public static $displayPositionMapping = [
        0 => 'feed顶部',
        1 => 'feed中部',
        2 => '全部展示',
    ];
}