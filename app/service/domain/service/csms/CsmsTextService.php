<?php


namespace Imee\Service\Domain\Service\Csms;

use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;

class CsmsTextService
{
    use CsmswarningTrait;
    use CsmsTrait;


    // 内置房间白名单
    public $_roomNames = array();


    private $_tableConfig = array(
        'xs_user_profile' => array(
            'pk' => 'uid',
            'fields' => array('name', 'sign'),
            'ingoreInsert' => false,
            'check_image' => true,
        ),
        'xs_user_photos' => array(
            'pk' => 'id',
            'fields' => array('path'),
            'ingoreInsert' => false,
            'check_image' => false,
        ),
        'xs_chatroom' => array(
            'pk' => 'rid',
            'fields' => array('name', 'description'),
            'ingoreInsert' => false,
            'check_image' => false,
        ),
        'xs_fleet' => array(
            'pk' => 'gid',
            'fields' => array('name', 'description', 'tmp_icon'),
            'ingoreInsert' => false,
            'check_image' => false,
        ),
        'xs_group' => array(
            'pk' => 'group_id',
            'fields' => array('name'),
            'ingoreInsert' => true,
            'check_image' => false,
        ),
        'xs_order_vote' => array(
            'pk' => 'id',
            'fields' => array('desc'),
            'ingoreInsert' => false,
            'check_image' => false,
        ),
    );


    public $config = [
        'xs_user_profile' => [
            'name' => [
                'choice' => 'xs_user_name',
                'type' => 'text'
            ],
            'sign' => [
                'choice' => 'xs_user_sign',
                'type' => 'text'
            ]
        ],
        'xs_user_profile1' => [
            'tmp_icon' => [
                'choice' => 'xs_user_icon',
                'type' => 'image'
            ]
        ],
        'god_user_icon' => [
            'tmp_icon' => [
                'god' => 'god_user_icon',
                'type' => 'image'
            ]
        ],
        'xs_user_photos' => [
            'path' => [
                'choice' => 'xs_user_photos',
                'type' => 'image'
            ]
        ],
        'xs_chatroom' => [
            'name' => [
                'choice' => 'xs_chatroom_name',
                'type' => 'text'
            ],
            'description' => [
                'choice' => 'xs_chatroom_description',
                'type' => 'text'
            ]
        ],
        'xs_group' => [
            'name' => [
                'choice' => 'xs_group_name',
                'type' => 'text'
            ]
        ],
        'xs_fleet' => [
            'name' => [
                'choice' => 'xs_fleet_name',
                'type' => 'text'
            ],
            'description' => [
                'choice' => 'xs_fleet_description',
                'type' => 'text'
            ],
            'tmp_icon' => [
                'choice' => 'xs_fleet_icon',
                'type' => 'image'
            ]
        ],
        'xs_fleet_icon' => [
            'tmp_icon' => [
                'choice' => 'xs_fleet_icon',
                'type' => 'image'
            ]
        ],
        'xs_order_vote' => [
            'desc' => [
                'choice' => 'xs_order_vote',
                'type' => 'text'
            ]
        ],
        'xs_welcome_text' => [
            'wel_text_verify' => [
                'choice' => 'xs_welcome_text',
                'type' => 'text'
            ]
        ],
        'xs_marry_relation' => [
            'temp_img_bg' => [
                'choice' => 'temp_img_bg',
                'type' => 'image'
            ]
        ],
        'xs_wedding_album' => [
            'image_url' => [
                'choice' => 'xs_wedding_album',
                'type' => 'image'
            ]
        ],
        'xs_live_config' => [
            'verify_text' => [
                'choice' => 'xs_live_config',
                'type' => 'text'
            ]
        ],
        'xs_marry_message' => [
            'message' => [
                'choice' => 'xs_marry_message',
                'type' => 'text'
            ]
        ],
        'xs_relation_defend' => [
            'diy_name' => [
                'choice' => 'xs_relation_defend',
                'type' => 'text'
            ]
        ]
    ];


    /**
     * csms text format
     * @param $data
     * @param null $messageId
     * @param null $timestamp
     * @return false
     */
    public function format($data, $messageId = null, $timestamp = null)
    {
        if (
            isset($data['table']) && isset($data['type'])
            && $data['table'] == "xs_user_profile" && $data['type'] == "update"
            && $data['data'][0]['after']['online_dateline'] < strtotime("2021-11-10")
        ) return false;

        if (isset($data['cmd'])) {
            $config = $data['data'];
            if (isset($config['table'])
                && isset($config['pkValue'])
                && isset($config['field'])
                && isset($config['content'])
            ) {
                switch ($data['cmd']) {
                    case 'add':
                        if ($config['table'] == 'xs_chat_message') {
                            //	$this->_addChatMessage($config);
                        } else {
                            $this->_addText(
                                $config['table'],
                                $config['pkValue'],
                                $config['field'],
                                "",
                                $config['content'],
                                $timestamp,
                                $config['review']
                            );
                        }
                        break;
                }
            }
            return false;
        } else if (isset($data['table']) && isset($data['type']) && isset($data['data'])) {
            //处理数据库变化
            $table = $data['table'];
            $type = $data['type'];
            foreach ($data['data'] as $item) {
                if (!isset($this->_tableConfig[$table])) {
                    continue;
                }
                $pkField = $this->_tableConfig[$table]['pk'];
                $fields = $this->_tableConfig[$table]['fields'];
                $ingoreInsert = $this->_tableConfig[$table]['ingoreInsert'];
                if ($type == 'write') {
                    if ($ingoreInsert) continue;
                    if (!isset($item[$pkField])) continue;
                    foreach ($fields as $field) {
                        if (!empty($item[$field])) {
                            $this->_addText($table, $item[$pkField], $field, '', $item[$field], $timestamp, false, $item);
                        }
                    }
                } else if ($type == 'update') {
                    if (!isset($item['after'][$pkField])) continue;
                    foreach ($fields as $field) {
                        if (!empty($item['after'][$field]) && $item['before'][$field] != $item['after'][$field]) {
                            $this->_addText(
                                $table,
                                $item['after'][$pkField],
                                $field,
                                $item['before'][$field],
                                $item['after'][$field],
                                $timestamp,
                                false,
                                $item['after']
                            );
                        }
                    }
                }
            }

            //兼容现在的逻辑
            //同时更新icon和tmp_icon,且icon == tmp_icon 表明先发后审
            //只更新tmp_icon表明先审后
            foreach ($data['data'] as $item) {
                if (!isset($this->_tableConfig[$table]) || !$this->_tableConfig[$table]['check_image']) {
                    continue;
                }
                $pkField = $this->_tableConfig[$table]['pk'];
                if ($type == 'write') {
                    if ($ingoreInsert) continue;
                    if (!isset($item[$pkField]) || !isset($item['icon']) || !isset($item['tmp_icon'])) continue;
                    if (!empty($item['tmp_icon']) && !empty($item['icon'])) {
                        $isGod = $item['role'] >= 2;
                        //新增的，必然同时有这两个内容
                        $this->_addVerifyImage(
                            $table,
                            $item[$pkField],
                            $item['icon'],
                            $item['tmp_icon'],
                            $timestamp,
                            false,
                            $isGod
                        );
                    }
                } else if ($type == 'update') {
                    if (!isset($item['after'][$pkField])) continue;
                    $iconChanged = $item['after']['icon'] != $item['before']['icon'];
                    $tmpIconChanged = $item['after']['tmp_icon'] != $item['before']['tmp_icon'];
                    if ($tmpIconChanged && !empty($item['after']['tmp_icon'])) {
                        if ($iconChanged || $item['after']['tmp_icon'] == $item['after']['icon']) {
                            $isReview = false; //这是先发后审
                            $originIcon = $item['before']['icon'];
                        } else {
                            $isReview = true;
                            $originIcon = $item['after']['icon'];
                        }
                        $isGod = $item['after']['role'] >= 2;
                        $this->_addVerifyImage(
                            $table,
                            $item['after'][$pkField],
                            $originIcon,
                            $item['after']['tmp_icon'],
                            $timestamp,
                            $isReview,
                            $isGod
                        );
                    }
                }
            }
        }
        return false;
    }


    /**
     * @param $table
     * @param $pkValue
     * @param $field
     * @param $origin
     * @param $content
     * @param $timestamp
     * @param false $review
     * @param null $originData
     * @return bool
     */
    private function _addText($table, $pkValue, $field, $origin, $content, $timestamp, $review = false, $originData = null)
    {
        if (empty($table) || empty($pkValue) || empty($field) || empty($content)) return false;
        if ($content == '伴伴网友'
            || $content == '无名'
            || $content == '无名氏'
            || $content == '我的房间我做主'
            || $content == '我的群组我做主'
            || $content == '我的家族我做主'
            || $content == 'Anonymous'
        ) {
            return false;
        }

        //对于房间名字，如果是系统内置的，不处理
        if ($table == 'xs_chatroom' && $field == 'name') {
            if (isset($this->_roomNames[$content])) {
                return false;
            }
        }

        $item = array(
            'table' => $table,
            'pkValue' => $pkValue,
            'field' => $field,
            'origin' => $origin,
            'content' => $content,
            'timestamp' => $timestamp,
            'review' => $review,
            'language' => '',
            'country' => ''
        );


        $csmsPush = [
            'choice' => $this->getCsmsChoice($item),
            'pk_value' => $pkValue,
            'review' => $review,
            'uid' => $this->getCsmsUid($item, $originData),
            'app_id' => APP_ID,
            'content' => [
                [
                    'field' => $field,
                    'type' => $this->getCsmsType($item),
                    'before' => [

                    ],
                    'after' => [
                        $content
                    ]
                ]
            ]
        ];
        if ($origin) {
            $csmsPush['content'][0]['before'][] = $origin;
        }
        \Imee\Models\Nsq\CsmsNsq::csmsPush($csmsPush);

        return true;
    }


    /**
     * 图片
     * @param $table
     * @param $pkValue
     * @param $origin
     * @param $content
     * @param $timestamp
     * @param false $isReview
     * @param false $isGod
     * @return bool
     */
    private function _addVerifyImage($table, $pkValue, $origin, $content, $timestamp, $isReview = false, $isGod = false)
    {
        if ($content == 'icon.png' || empty($content)) return false;
        $item = array(
            'table' => $table,
            'field' => 'tmp_icon',
            'pk_value' => $pkValue,
            'origin' => $origin,
            'value' => $content,
            'reason' => '图片:含有违规内容',
            'deleted' => 2,
            'review' => $isReview ? 1 : 0,
        );

        $csmsPush = [
            'choice' => 'xs_user_icon',
            'pk_value' => $item['pk_value'],
            'uid' => $item['pk_value'],
            'app_id' => APP_ID,
            'review' => $item['review'],
            'content' => [
                [
                    'field' => 'tmp_icon',
                    'type' => 'image',
                    'before' => [
                        $origin
                    ],
                    'after' => [
                        $content
                    ]
                ]
            ]
        ];
        \Imee\Models\Nsq\CsmsNsq::csmsPush($csmsPush);
        return true;
    }




    public function getCsmsChoice($data = [])
    {
        $ochoice = $this->getTextChoice($data);
        return $this->config[$ochoice][$data['field']]['choice'];
    }


    public function getCsmsType($data)
    {
        $ochoice = $this->getTextChoice($data);
        return $this->config[$ochoice][$data['field']]['type'];
    }


    public function getCsmsUid($data, $origin = [])
    {
        $uid = 0;
        switch ($data['table']) {
            case 'xs_user_profile':
            case 'xs_user_photos':
            case 'xs_chatroom':
            case 'xs_fleet':
            case 'xs_order_vote':
                // 少数几条先审后发的数据是业务投递过来的，不是binlog监听，不能从origin取用户信息
                if($data['table'] == 'xs_user_profile'){
                    $uid = $data['pkValue'];
                }else{
                    $uid = $origin['uid'];
                }
                break;
            case 'xs_group':
                $uid = $origin['createor'];
                break;
            default:
                break;
        }
        return $uid;
    }


}