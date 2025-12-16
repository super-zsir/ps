<?php


namespace Imee\Service\Domain\Service\Csms;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Nsq\CsmsNsq;
use Imee\Models\Xs\XsChatroom;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;

class CsmsBinlogService
{

    use CsmswarningTrait;

    public $binlogData;

    public function binlog($data)
    {
        $this->binlogData = $data;

        switch ($data['table']){
            // 谁是凶手-房间头像
            case 'games_room_icon_audit':
                $result = $this->xiongshouChatroomIcon($data);
                break;
            // 声音审核
//            case 'xs_rush_user_friend_card':
//                $result = $this->friendCard($data);
//                break;
            // 联盟审核
//            case 'bbu_union':
//                $result = $this->bbuUnion($data);
//                break;
            // 粉丝牌审核
            case 'xs_live_config':
                $result = $this->liveConfig($data);
                break;
            // 房间公屏图片
            case 'xs_screen_image':
                $result = $this->screenImage($data);
                break;
            // 订单评论
            case 'xs_order_vote':
                $result = $this->orderVote($data);
                break;
            // 房间修改
            case 'xs_chatroom_modify':
                $result = $this->chatroomModify($data);
                break;
            case 'xs_family':
                $result = $this->xsFamily($data);
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }


    /**
     * csmspush 投递
     * @param array $csmsData
     * @return bool
     * @throws \Exception
     */
    public function csmsPush(array $csmsData = []): bool
    {
        if($csmsData){

            if (ENV == 'dev') {
                $csmsPush = [
                    'cmd' => 'csms.push',
                    'data' => $csmsData
                ];
                print_r($csmsPush);
                // 数据清洗 - 守护进程的类都必须用单例模式
                $dataCleanService = new \Imee\Service\Domain\Service\Csms\Task\DataCleaningService();
                $cleanData = $dataCleanService->handle($csmsPush, 'nsq');

                // 送风控系统
                if ($cleanData) {
                    $service = new \Imee\Service\Domain\Service\Csms\SaasService();;
                    $return = $service->initData($cleanData);
                } else {
                    $return = false;
                }
                return $return;
            }


            $result = CsmsNsq::csmsPush($csmsData);

            // 投递失败的
            if($result){
                // 发送失败
                $wechatContent = <<<STR
csms.binlog 发送失败 【！！！重要！！！】
> BINLOG数据: {binlogdata}
> CSMSPUSH数据: {csmsdata}
> 失败原因: {reason}
> DATE: {date}
STR;
                $wechatMsg = str_replace(
                    ['{binlogdata}', '{csmsdata}', '{reason}', '{date}'],
                    [json_encode($this->binlogData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($csmsData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $result, date('Y-m-d H:i:s')],
                    $wechatContent
                );
                $this->sendCsms($wechatMsg);
                return false;
            }else{
                // 投递成功的
                return true;
            }
        }else{
            // 空数据，不投递
            return true;
        }

    }


    /**
     * 谁是凶手 - 房间头像
     * @param $data
     */
    public function xiongshouChatroomIcon($data)
    {
        switch ($data['type']){
            case "write":
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        $csmsData = [
                            'choice' => 'games_room_icon',
                            'pk_value' => $value['id'],
                            'uid' => $value['uid'],
                            'review' => true,
                            'time' => $value['create_time'],
                            'content' => [
                                [
                                    'field' => 'icon',
                                    'type' => 'image',
                                    'before' => [

                                    ],
                                    'after' => [
                                        CDN_IMG_DOMAIN.$value['icon']
                                    ]
                                ]
                            ]
                        ];
                        $result = $this->csmsPush($csmsData);
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }


    /**
     * 声音审核
     * @param $data
     */
    public function friendCard($data)
    {
        $result = false;
        switch ($data['type']){
            case "write":
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        if(!$value['checked'] && $value['audio']) {

                            $csmsData = [
                                'choice' => 'friend_card',
                                'pk_value' => $value['uid'],
                                'uid' => $value['uid'],
                                'review' => true,
                                'content' => [
                                    [
                                        'field' => 'audio',
                                        'type' => 'audio',
                                        'before' => [],
                                        'after' => [CDN_VOICE_DOMAIN . $value['audio']],
                                    ]
                                ]
                            ];
                            $result = $this->csmsPush($csmsData);
                        }
                    }
                }
                break;
            case 'update':
                foreach ($data['data'] as $key => $value){
                    $after = $value['after'];
                    $before = $value['before'];
                    if($before['audio'] != $after['audio'] && $after['audio'] && !$after['checked']){

                        $csmsData = [
                            'choice' => 'friend_card',
                            'pk_value' => $after['uid'],
                            'uid' => $after['uid'],
                            'review' => true,
                            'content' => [
                                [
                                    'field' => 'audio',
                                    'type' => 'audio',
                                    'before' => [CDN_VOICE_DOMAIN.$before['audio']],
                                    'after' => [CDN_VOICE_DOMAIN.$after['audio']],
                                ]
                            ]
                        ];
                        $result = $this->csmsPush($csmsData);
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }


    /**
     * 联盟审核
     * @param $data
     * @return bool
     */
    public function bbuUnion($data)
    {
        switch ($data['type']){
            case "write":
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        if($value['temp_status'] == 10 && (!$value['temp_name'] || !$value['temp_short_name'] || !$value['temp_logo'] || !$value['temp_bg_pic']) || !$value['temp_desc']) {
                            $csmsData = [
                                'choice' => 'bbu_union',
                                'pk_value' => $value['id'],
                                'uid' => $value['create_uid'],
                                'review' => true,
                                'content' => [

                                ]
                            ];
                            if($value['temp_name']) $csmsData['content'][] = ['field' => 'temp_name', 'type' => 'text', 'before' => [], 'after' => [$value['temp_name']]];
                            if($value['temp_short_name']) $csmsData['content'][] = ['field' => 'temp_short_name', 'type' => 'text', 'before' => [], 'after' => [$value['temp_short_name']]];
                            if($value['temp_desc']) $csmsData['content'][] = ['field' => 'temp_desc', 'type' => 'text', 'before' => [], 'after' => [$value['temp_desc']]];
                            if($value['temp_logo']) $csmsData['content'][] = ['field' => 'temp_logo', 'type' => 'image', 'before' => [], 'after' => [CDN_IMG_DOMAIN.$value['temp_logo']]];
                            if($value['temp_bg_pic']) $csmsData['content'][] = ['field' => 'temp_bg_pic', 'type' => 'image', 'before' => [], 'after' => [CDN_IMG_DOMAIN.$value['temp_bg_pic']]];

                            $result = $this->csmsPush($csmsData);
                        }
                    }
                }
                break;
            case 'update':
                foreach ($data['data'] as $key => $value){
                    $after = $value['after'];
                    $before = $value['before'];
                    if($after['temp_status'] == 10 && (!$after['temp_name'] || !$after['temp_short_name'] || !$after['temp_logo'] || !$after['temp_bg_pic']) || !$after['temp_desc']) {
                        $csmsData = [
                            'choice' => 'bbu_union',
                            'pk_value' => $after['id'],
                            'uid' => $after['create_uid'],
                            'review' => true,
                            'content' => [

                            ]
                        ];
                        if($after['temp_name'] && ($after['temp_name'] != $before['temp_name'])) $csmsData['content'][] = ['field' => 'temp_name', 'type' => 'text', 'before' => [$before['temp_name']], 'after' => [$after['temp_name']]];
                        if($after['temp_short_name'] && ($after['temp_short_name'] != $before['temp_short_name'])) $csmsData['content'][] = ['field' => 'temp_short_name', 'type' => 'text', 'before' => [$before['temp_short_name']], 'after' => [$after['temp_short_name']]];
                        if($after['temp_desc'] && ($after['temp_desc'] != $before['temp_desc'])) $csmsData['content'][] = ['field' => 'temp_desc', 'type' => 'text', 'before' => [$before['temp_desc']], 'after' => [$after['temp_desc']]];
                        if($after['temp_logo'] && ($after['temp_logo'] != $before['temp_logo'])) $csmsData['content'][] = ['field' => 'temp_logo', 'type' => 'image', 'before' => [$before['temp_logo']], 'after' => [CDN_IMG_DOMAIN.$after['temp_logo']]];
                        if($after['temp_bg_pic'] && ($after['temp_bg_pic'] != $before['temp_bg_pic'])) $csmsData['content'][] = ['field' => 'temp_bg_pic', 'type' => 'image', 'before' => [$before['temp_bg_pic']], 'after' => [CDN_IMG_DOMAIN.$after['temp_bg_pic']]];

                        // 有更改内容才 推送
                        if(!empty($csmsData['content'])){
                            $result = $this->csmsPush($csmsData);
                        }else{
                            $result = true;
                        }
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }



    /**
     * 粉丝牌审核 入文本
     * @param $data
     */
    private function liveConfig($data)
    {
        $result = true;
        switch ($data['type']){
            case 'write':
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        if($value['verify_text'] && !$value['is_verify']){

                            $csmsData = [
                                'choice' => 'xs_live_config',
                                'pk_value' => $value['id'],
                                'uid' => $value['live_uid'],
                                'review' => true,
                                'content' => [
                                    [
                                        'field' => 'verify_text',
                                        'type' => 'text',
                                        'before' => [],
                                        'after' => [
                                            $value['verify_text']
                                        ]
                                    ]
                                ]
                            ];

                            $result = $this->csmsPush($csmsData);
                        }
                    }
                }
                break;
            case 'update':
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        $after = $value['after'];
                        $before = $value['before'];
                        // 只有 待审核，有变化的才进审核
                        if($after['verify_text'] && $after['verify_text'] != $before['verify_text'] && !$after['is_verify']){
                            $csmsData = [
                                'choice' => 'xs_live_config',
                                'pk_value' => $after['id'],
                                'uid' => $after['live_uid'],
                                'review' => true,
                                'content' => [
                                    [
                                        'field' => 'verify_text',
                                        'type' => 'text',
                                        'before' => [
                                            $before['verify_text']
                                        ],
                                        'after' => [
                                            $after['verify_text']
                                        ]
                                    ]
                                ]
                            ];
                            $result = $this->csmsPush($csmsData);
                        }
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }


    /**
     * 房间公屏图
     * @param $data
     */
    public function screenImage($data)
    {
        $result = true;

        switch ($data['type']){
            case 'write':
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        // 图片是 先发后审，视频是先审后发
                        $csmsData = [
                            'choice' => 'xs_screen_image',
                            'pk_value' => $value['id'],
                            'uid' => $value['uid'],
                            'review' => $value['status'] ? false : true,
                            'content' => [
                                [
                                    'field' => 'content',
                                    'type' => $value['type'] == 'image' ? 'image' : 'video',
                                    'before' => [

                                    ],
                                    'after' => [
                                        CDN_IMG_DOMAIN.($value['type'] == 'image' ? $value['image'] : $value['vedio'])
                                    ]
                                ]
                            ]
                        ];

                        $result = $this->csmsPush($csmsData);
                    }
                }
                break;
            case 'update':
                if($data['data']){
                    // 目前没有修改功能
                    foreach ($data['data'] as $key => $value){
                        $after = $value['after'];
                        $before = $value['before'];
                        // 只有 待审核，有变化的才进审核
                        if(!$after['status']){
                            $csmsData = [
                                'choice' => 'xs_screen_image',
                                'pk_value' => $after['id'],
                                'uid' => $after['uid'],
                                'review' => true,
                                'content' => [

                                ]
                            ];
                            if($after['type'] == 'image'){
                                if($after['image'] != $before['image']){
                                    $csmsData['content'][] = [
                                        'field' => 'content',
                                        'type' => 'image',
                                        'before' => [
                                            CDN_IMG_DOMAIN.$before['image']
                                        ],
                                        'after' => [
                                            CDN_IMG_DOMAIN.$after['image']
                                        ]
                                    ];
                                }
                            }
                            if($after['type'] == 'vedio'){
                                if($after['vedio'] != $before['vedio']){
                                    $csmsData['content'][] = [
                                        'field' => 'content',
                                        'type' => 'video',
                                        'before' => [
                                            CDN_IMG_DOMAIN.$before['vedio']
                                        ],
                                        'after' => [
                                            CDN_IMG_DOMAIN.$after['vedio']
                                        ]
                                    ];
                                }
                            }

                            // 有更改内容才 推送
                            if(!empty($csmsData['content'])){
                                $result = $this->csmsPush($csmsData);
                            }else{
                                $result = true;
                            }
                        }
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }

    /**
     * 订单评论
     * @param $data
     */
    public function orderVote($data)
    {
        switch ($data['type']){
            case 'write':
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        if($value['desc']){
                            $csmsData = [
                                'choice' => 'xs_order_vote',
                                'pk_value' => $value['id'],
                                'uid' => $value['uid'],
                                'review' => false,
                                'time' => $value['dateline'],
                                'content' => [
                                    [
                                        'field' => 'desc',
                                        'type' => 'text',
                                        'before' => [

                                        ],
                                        'after' => [
                                            $value['desc']
                                        ]
                                    ]
                                ]
                            ];
                            $result = $this->csmsPush($csmsData);
                        }
                    }
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;

    }

    /**
     * 聊天室修改
     */
    public function chatroomModify($data)
    {
        switch ($data['type']){
            case 'write':
                if($data['data']){
                    foreach ($data['data'] as $key => $value){
                        // 房间头像修改
                        if($value['type'] == 'icon' && !$value['state'] && $value['val']){
                            // 获取房主信息
                            $chatroom = XsChatroom::useMaster()->findFirst([
                                'conditions' => 'rid = :rid:',
                                'bind' => [
                                    'rid' => $value['uid']
                                ]
                            ]);
                            if($chatroom){
                                $csmsData = [
                                    'choice' => 'xs_chatroom_icon',
                                    'pk_value' => $value['id'],
                                    'uid' => $chatroom->uid,
                                    'review' => false,
                                    'time' => $value['dateline'],
                                    'content' => [
                                        [
                                            'field' => 'icon',
                                            'type' => 'image',
                                            'before' => [

                                            ],
                                            'after' => [
                                                $value['val']
                                            ]
                                        ]
                                    ]
                                ];
                                $result = $this->csmsPush($csmsData);
                            }else{
                                // 房间不存在 发个预警
                                $wecontent = <<<STR
房间封面数据同步未找到房间信息
> 房间ID: {rid}
> BINLOG: {binlog}
> DATE: {date}
STR;
                                $wechatMsg = str_replace(
                                    ['{rid}', '{binlog}', '{date}'],
                                    [$value['uid'], json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
                                    $wecontent
                                );
                                $this->sendCsms($wechatMsg);
                                $result = false;
                            }
                        }else{
                            // 已审核 或不是头像，直接不要
                            $result = true;
                        }
                    }
                }else{
                    // 無数据的不要
                    $result = true;
                }
                break;
            default:
                $result = true;
                break;
        }
        return $result;
    }


    /**
     * 新家族审核
     * @param $data
     */
    public function xsFamily($data)
    {
        $result = true;
        switch ($data['type']){
            case 'write':
                foreach ($data['data'] as $key => $value){
                    if($value['name']){
                        $csmsData = [
                            'choice' => 'family_name',
                            'pk_value' => $value['fid'],
                            'uid' => $value['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'name',
                                    'type' => 'text',
                                    'before' => [],
                                    'after' => [$value['name']],
                                ]
                            ]
                        ];
                        $this->csmsPush($csmsData);
                    }
                    if($value['announcement']){
                        $csmsData = [
                            'choice' => 'family_announcement',
                            'pk_value' => $value['fid'],
                            'uid' => $value['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'announcement',
                                    'type' => 'text',
                                    'before' => [],
                                    'after' => [$value['announcement']],
                                ]
                            ]
                        ];
                        $this->csmsPush($csmsData);
                    }
                    if($value['badge']){
                        $csmsData = [
                            'choice' => 'family_badge',
                            'pk_value' => $value['fid'],
                            'uid' => $value['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'badge',
                                    'type' => 'image',
                                    'before' => [],
                                    'after' => [$value['badge']],
                                ]
                            ]
                        ];
                        $this->csmsPush($csmsData);
                    }
                }
                break;
            case 'update':
                foreach ($data['data'] as $key => $value){
                    $after = $value['after'];
                    $before = $value['before'];
                    if($before['name'] != $after['name'] && $after['name']){

                        // 默认 名字 family_ + fid 不审核
                        if(strpos($after['name'], 'family_') === 0){
                            continue;
                        }

                        $csmsData = [
                            'choice' => 'family_name',
                            'pk_value' => $after['fid'],
                            'uid' => $after['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'name',
                                    'type' => 'text',
                                    'before' => [$before['name']],
                                    'after' => [$after['name']],
                                ]
                            ]
                        ];
                        $result = $this->csmsPush($csmsData);
                    }
                    if($before['announcement'] != $after['announcement'] && $after['announcement']){
                        $csmsData = [
                            'choice' => 'family_announcement',
                            'pk_value' => $after['fid'],
                            'uid' => $after['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'announcement',
                                    'type' => 'text',
                                    'before' => [$before['announcement']],
                                    'after' => [$after['announcement']],
                                ]
                            ]
                        ];
                        $result = $this->csmsPush($csmsData);
                    }
                    if($before['badge'] != $after['badge'] && $after['badge']){
                        // 默认徽章 不审核
                        if($after['badge'] == '202212/14/28867323736399778d794487.46124719.jpeg'){
                            continue;
                        }
                        $csmsData = [
                            'choice' => 'family_badge',
                            'pk_value' => $after['fid'],
                            'uid' => $after['uid'],
                            'review' => false,
                            'content' => [
                                [
                                    'field' => 'badge',
                                    'type' => 'image',
                                    'before' => [$before['badge']],
                                    'after' => [$after['badge']],
                                ]
                            ]
                        ];
                        $result = $this->csmsPush($csmsData);
                    }
                }
                break;
            default:
                break;
        }
        return $result;
    }

}