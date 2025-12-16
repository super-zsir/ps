<?php

namespace Imee\Service\Domain\Service\Audit\Traits;

trait AuditTrait
{
    /**
     * 审核系统 - 文本、机审 审核选项
     * @var array
     */
    private $shenheChoice = [
        'xs_user_profile'  => '用户昵称/签名',
        'xs_user_profile1' => '用户头像',
        'xs_user_photos'   => '用户形象照',
        'xs_chatroom'      => '房间名字/公告',
        'xs_group'         => '群组名字',
        'xs_fleet'         => '家族标题/介绍',
        'xs_fleet_icon'    => '家族封面',
        'xs_order_vote'    => '订单评论',
        'xs_welcome_text'  => '迎新招呼',
    ];

    private $sex = [
        '1' => '男',
        '2' => '女'
    ];
}
