<?php

namespace Imee\Comp\Common\Sdk;

use Imee\Comp\Common\Fixed\Utility;
use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsUserProfile;
use phalcon\Cache\Exception;
use Imee\Service\Helper;
use Config\ConfigIm;

class SdkIm extends SdkBase
{
    private $format = 'json';

    private $appKey;
    private $appSecret;

    const SERVERAPIURL = 'https://api.cn.ronghub.com';//请求服务地址

    const SYSTEM_NORMAL = 1; //系统消息
    const SYSTEM_WEEKEND = 2; //ee周末
    const SYSTEM_LOVE = 3; //暗恋表白
    const SYSTEM_WECHAT = 4; //玩微信
    const SYSTEM_DATE = 5; //ee约会
    const SYSTEM_PARTY = 6; //ee聚会
    const SYSTEM_QQ = 7; //QQ
    const SYSTEM_DRIFT = 8; //漂流瓶

    private $_use_async = false;
    private $_env;

    private $_configIm;

    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
        $this->_env = ENV;
        $this->_configIm = new ConfigIm();
        $config = ConfigIm::getAppEnv(ENV);
        $this->appKey = $config['appKey'];
        $this->appSecret = $config['appSecret'];
    }

    public static function getSecret()
    {
        $config = ConfigIm::getAppEnv(ENV);
        return $config['appSecret'];
    }

    public static function getAppkey()
    {
        $config = ConfigIm::getAppEnv(ENV);
        return $config['appKey'];
    }

    public function switchEnv($env)
    {
        $this->_env = $env;
    }

    public function useAsync($async)
    {
        $this->_use_async = $async;
    }

    public static function verify($nonce, $timestamp, $signature)
    {
        return ConfigIm::verify($nonce, $timestamp, $signature);
    }

    //提示条（小灰条）通知消息聊天，此类型消息没有 Push 通知
    //rush_begin 冲鸭添加 $isIncludeSender 参数，用于服务端发送消息时为发送者也保留一份
    public function notify($uid, $to_uid, $message, $extra = null, $isSystem = false, $withUser = true, $isIncludeSender = false, $pushData = '')
    {
        return $this->message($uid, $to_uid, $message, $extra, null, $isSystem, 'RC:InfoNtf', $withUser, $isIncludeSender, $pushData);
    }
    //rush_end

    //统一的消息发送
    public function message($uid, $to_uid, $message, $extra = null, $pushContent = null, $isSystem = false, $type = 'RC:TxtMsg', $withUser = true, $isIncludeSender = false, $pushData = '')
    {
        if ($type == 'RC:InfoNtf') {
            $array = array(
                'message' => $message,
            );
        } elseif ($type == 'RC:VcMsg') {
            $message = json_decode($message, 1);
            $array = [
                'content' => $message['content'],       //老版语音为base64码
                'duration' => $message['duration'],     //语音消息的时长, 单位秒
            ];
        } else {
            $array = array(
                'content' => $message,
            );
        }
        if (!is_null($extra)) {
            $array['extra'] = json_encode($extra);
        }
        if ($withUser) {
            $profile = XsUserProfile::findFirstValue(intval($uid));
            if (!empty($profile)) {
                $array['user'] = array(
                    'id' => strval($uid),
                    'name' => trim($profile->name),
                    'icon' => trim($profile->icon),
                );
            }
        }
        if ($isSystem) {
            if (!$pushContent) {
                $pushContent = "[系统]" . $message;
            }
            return $this->messageSystemPublish(
                $uid,
                is_array($to_uid) ? $to_uid : array($to_uid),
                $type,
                json_encode($array),
                $pushContent,
                $pushData,
                $isIncludeSender
            );
        } else {
            if (!$pushContent) {
                if (!is_array($message)) {
                    $pushContent = $message;
                }
            }

            return $this->messagePublish(
                $uid,
                is_array($to_uid) ? $to_uid : array($to_uid),
                $type,
                json_encode($array),
                $pushContent,
                $pushData,
                $isIncludeSender
            );
        }
    }

    //自定义命令消息，不通知、不存储、不计数
    public function cmd($to_uid, $name, $data, $uid = 1)
    {
        $array = array(
            'name' => $name,
            'data' => json_encode($data)
        );
        return $this->messagePublish(
            $uid,
            is_array($to_uid) ? $to_uid : array($to_uid),
            'RC:CmdMsg',
            json_encode($array)
        );
    }

    /**
     * 获取 Token 方法
     * @param $userId   用户 Id，最大长度 32 字节。是用户在 App 中的唯一标识码，必须保证在同一个 App 内不重复，重复的用户 Id 将被当作是同一用户。
     * @param $name     用户名称，最大长度 128 字节。用来在 Push 推送时，或者客户端没有提供用户信息时，显示用户的名称。
     * @param $portraitUri  用户头像 URI，最大长度 1024 字节。
     * @return json|xml
     * @throws Exception
     */
    public function getToken($userId, $name, $portraitUri)
    {
        if (empty($userId)) {
            throw new Exception('用户 Id 不能为空');
        }
        if (empty($name)) {
            throw new Exception('用户名称 不能为空');
        }
        if (empty($portraitUri)) {
            throw new Exception('用户头像 URI 不能为空');
        }

        return $this->curl('/user/getToken', array('userId' => $userId, 'name' => $name, 'portraitUri' => $portraitUri));
    }

    public function isReadMessage($objectName)
    {
        return !in_array($objectName, array('RC:CmdMsg', 'RC:InfoNtf'));
    }

    private function encodeUid($uid)
    {
        if (is_array($uid)) {
            return array_map('strval', $uid);
        } else {
            return strval($uid);
        }
    }

    /**
     * 发送会话消息(单聊)
     * @param $fromUserId   发送人用户 Id。（必传）
     * @param $toUserId     接收用户 Id，提供多个本参数可以实现向多人发送消息。（必传）
     * @param $objectName   消息类型，参考融云消息类型表.消息标志；可自定义消息类型。（必传）
     * @param $content      发送消息内容，参考融云消息类型表.示例说明；如果 objectName 为自定义消息类型，该参数可自定义格式。（必传）
     * @param string $pushContent 如果为自定义消息，定义显示的 Push 内容。(可选)
     * @param string $pushData 针对 iOS 平台，Push 通知附加的 payload 字段，字段名为 appData。(可选)
     * @return json|xml
     * $im->messagePublish(8, array(8, 9), 'RC:TxtMsg', json_encode(array('content' =>'Hello Test messagePublish')));
     * @throws Exception
     */
    public function messagePublish($fromUserId, $toUserId = array(), $objectName, $content, $pushContent = '', $pushData = '', $isIncludeSender = false, $isSync = 0)
    {
        if (empty($fromUserId)) {
            throw new Exception('发送人用户 Id 不能为空');
        }
        if (empty($toUserId)) {
            throw new Exception('接收用户 Id 不能为空');
        }
        if (empty($objectName)) {
            throw new Exception('消息类型 不能为空');
        }
        if (empty($content)) {
            throw new Exception('发送消息内容 不能为空');
        }

        // 测试环境 或者线上 客服皮小妹 全量走 自研IM
        //		if(in_array($fromUserId, ['103700335', '100000018', '100000020', '100000016', '100000022', '100000017', '100000023', '100000024', '100000025']) || (ENV == 'dev')){
        //			// 自研im
        //			$imBanban = new SdkBanBanIm();
        //			$imBanban->useAsync(true);
        //			return $imBanban->messagePublish($fromUserId, $toUserId, $objectName, $content, $pushContent, $pushData, $isIncludeSender, $isSync);
        //		}else{
        // 自研im
        $imBanban = new SdkBanBanIm();
        $imBanban->useAsync(true);
        return $imBanban->messagePublish($fromUserId, $toUserId, $objectName, $content, $pushContent, $pushData, $isIncludeSender, $isSync);

        if (empty($pushContent) && $this->isReadMessage($objectName)) {
            $json = @json_decode($content, true);
            if ($json && !empty($json['content'])) {
                $pushContent = $json['content'];
            }
        }
        $params = array(
                'fromUserId' => $this->encodeUid($fromUserId),
                'objectName' => $objectName,
                'content' => $content,
                'pushContent' => $pushContent,
                'pushData' => $pushData,
                'toUserId' => $this->encodeUid($toUserId),
                'isIncludeSender' => $isIncludeSender ? 1 : 0
            );
        return $this->curl('/message/private/publish', $params);
        //		}
    }

    /**
     * 一个用户向一个或多个用户发送系统消息
     * @param $fromUserId       发送人用户 Id。（必传）
     * @param $toUserId         接收用户Id，提供多个本参数可以实现向多用户发送系统消息。（必传）
     * @param $objectName       消息类型，参考融云消息类型表.消息标志；可自定义消息类型。（必传）
     * @param $content          发送消息内容，参考融云消息类型表.示例说明；如果 objectName 为自定义消息类型，该参数可自定义格式。（必传）
     * @param string $pushContent 如果为自定义消息，定义显示的 Push 内容。(可选)
     * @param string $pushData 针对 iOS 平台，Push 通知附加的 payload 字段，字段名为 appData。(可选)
     * @return json|xml
     * @throws Exception
     */
    public function messageSystemPublish($fromUserId, $toUserId = array(), $objectName, $content, $pushContent = '', $pushData = '', $isIncludeSender = false)
    {
        if (empty($fromUserId)) {
            throw new Exception('发送人用户 Id 不能为空');
        }
        if (empty($toUserId)) {
            throw new Exception('接收用户 Id 不能为空');
        }
        if (empty($objectName)) {
            throw new Exception('消息类型 不能为空');
        }
        if (empty($content)) {
            throw new Exception('发送消息内容 不能为空');
        }

        // 自研im
        $imBanban = new SdkBanBanIm();
        $imBanban->useAsync(true);
        return $imBanban->messageSystemPublish($fromUserId, $toUserId, $objectName, $content, $pushContent, $pushData);

        if (empty($pushContent) && $this->isReadMessage($objectName)) {
            $json = @json_decode($content, true);
            if ($json) {
                $pushContent = $json['content'];
            }
        }
        $params = array(
            'fromUserId' => $fromUserId,
            'objectName' => $objectName,
            'content' => $content,
            'pushContent' => $pushContent,
            'pushData' => $pushData,
            'toUserId' => $this->encodeUid($toUserId),
            'isIncludeSender' => $isIncludeSender ? 1 : 0
        );

        return $this->curl('/message/system/publish', $params);
    }

    /**
     * 以一个用户身份向群组发送消息
     * @param $fromUserId 发送人用户 Id。（必传）
     * @param $toGroupId 接收群Id，提供多个本参数可以实现向多群发送消息。（必传）
     * @param $objectName 消息类型，参考融云消息类型表.消息标志；可自定义消息类型。（必传）
     * @param $content 发送消息内容，参考融云消息类型表.示例说明；如果 objectName 为自定义消息类型，该参数可自定义格式。（必传）
     * @param string $pushContent 如果为自定义消息，定义显示的 Push 内容。(可选)
     * @param string $pushData 针对 iOS 平台，Push 通知附加的 payload 字段，字段名为 appData。(可选)
     * @param bool $isIncludeSender 是否给发送者也添加一条消息(可选)
     * @return json|xml
     * @throws Exception
     */
    /*
    content {"content":"hello","extra":"helloExtra"}
    */
    //rush_begin 冲鸭增加 $isIncludeSender 参数，用于服务端群发消息时为发送者保留一份已发送的消息
    public function messageGroupPublish($fromUserId, $toGroupId, $objectName, $content, $pushContent = '', $pushData = '', $isIncludeSender = false)
    {
        if (empty($toGroupId)) {
            throw new Exception('接收群Id 不能为空');
        }
        if (empty($objectName)) {
            throw new Exception('消息类型 不能为空');
        }
        if (empty($content)) {
            throw new Exception('发送消息内容 不能为空');
        }
        if (empty($pushContent) && $this->isReadMessage($objectName)) {
            $json = @json_decode($content, true);
            if ($json) {
                $pushContent = $json['message'];
            }
        }

        // 自研im
        $imBanban = new SdkBanBanIm();
        $imBanban->useAsync(true);
        return $imBanban->messageGroupPublish($fromUserId, $toGroupId, $objectName, $content, $pushContent, $pushData, $isIncludeSender);

        $params = array(
            'fromUserId' => $fromUserId > 0 ? $this->encodeUid($fromUserId) : 1, //用于向该组里所有用户发送，fromUserId = 0
            'objectName' => $objectName,
            'content' => $content,
            'pushContent' => $pushContent,
            'pushData' => $pushData,
            'toGroupId' => $this->encodeUid($toGroupId),
            'isIncludeSender' => ($isIncludeSender ? 1 : 0)
        );

        return $this->curl('/message/group/publish', $params);
    }

    public function messageChatroomPublish($fromUserId, $toChatroomId = array(), $objectName, $content, $autoForward = true, $async = false)
    {
        if (empty($fromUserId)) {
            throw new Exception('发送人用户 Id 不能为空');
        }
        if (empty($toChatroomId)) {
            throw new Exception('接收聊天室Id 不能为空');
        }
        if (empty($objectName)) {
            throw new Exception('消息类型 不能为空');
        }
        if (empty($content)) {
            throw new Exception('发送消息内容 不能为空');
        }
        $params = array(
            'fromUserId' => $fromUserId > 0 ? $this->encodeUid($fromUserId) : 1, //用于向该组里所有用户发送，fromUserId = 0
            'objectName' => $objectName,
            'content' => $content,
            'toChatroomId' => $this->encodeUid($toChatroomId),
        );

        //兼容数据，通过自行渠道发送
        //&& ($toChatroomId == 100010055 || $toChatroomId == 'cmd.100010055')
        if ($autoForward) {
            NsqClient::publish(NsqConstant::TOPIC_XS_CHATROOM_MESSAGE, array(
                'cmd' => 'server.message',
                'time' => Utility::microtimeFloat(),
                'data' => $params
            ));
        }

        return array('code' => 200);

        $r = $this->curl('/message/chatroom/publish', $params, $async);

        return $r;
    }


    /**
     * 某发送消息给一个应用下的所有注册用户。
     * @param $fromUserId 发送人用户 Id。（必传）
     * @param $objectName 消息类型，参考融云消息类型表.消息标志；可自定义消息类型。（必传）
     * @param $content 发送消息内容，参考融云消息类型表.示例说明；如果 objectName 为自定义消息类型，该参数可自定义格式。（必传）
     * @return json|xml
     */
    public function messageBroadcast($fromUserId, $objectName, $content)
    {
        if (empty($fromUserId)) {
            throw new Exception('发送人用户 Id 不能为空');
        }
        if (empty($objectName)) {
            throw new Exception('消息类型不能为空');
        }
        if (empty($content)) {
            throw new Exception('发送消息内容不能为空');
        }
        return $this->curl(
            '/message/broadcast',
            array(
                'fromUserId' => $fromUserId,
                'objectName' => $objectName,
                'content' => $content
            )
        );
    }

    /**
     * 检查用户在线状态 方法
     * @param $userId 用户 Id。（必传）
     * @return mixed
     * @throws Exception
     */
    public function userCheckOnline($userId)
    {
        if (empty($userId)) {
            throw new Exception('用户 Id 不能为空');
        }
        return $this->curl('/user/checkOnline', array('userId' => $this->encodeUid($userId)));
    }

    /**
     * 封禁用户 方法
     * @param $userId   用户 Id。（必传）
     * @param $minute   封禁时长,单位为分钟，最大值为43200分钟。（必传）
     * @return mixed
     * @throws Exception
     */
    public function userBlock($userId, $minute)
    {
        $banbanSdk = new SdkBanBanIm();
        return $banbanSdk->userBlock($userId, $minute);

        if ($banbanSdk->isGrayUser($userId)) {
            return $banbanSdk->userBlock($userId, $minute);
        } else {
            if (empty($userId)) {
                throw new Exception('用户 Id 不能为空');
            }
            if (empty($minute)) {
                throw new Exception('封禁时长不能为空');
            }
            return $this->curl('/user/block', array('userId' => $this->encodeUid($userId), 'minute' => $minute));
        }
    }

    /**
     * 解除用户封禁 方法
     * @param $userId   用户 Id。（必传）
     * @return mixed
     * @throws Exception
     */
    public function userUnBlock($userId)
    {
        $banbanSdk = new SdkBanBanIm();
        return $banbanSdk->userUnBlock($userId);

        if ($banbanSdk->isGrayUser($userId)) {
            return $banbanSdk->userUnBlock($userId);
        } else {
            if (empty($userId)) {
                throw new Exception('用户 Id 不能为空');
            }
            return $this->curl('/user/unblock', array('userId' => $this->encodeUid($userId)));
        }
    }

    /**
     * 获取被封禁用户 方法
     * @return mixed
     */
    public function userBlockQuery()
    {
        return $this->curl('/user/block/query', '');
    }

    public function userBlacklistAdd($userId, $blackUserId)
    {
        $banbanSdk = new SdkBanBanIm();
        $banbanSdk->useAsync($this->_use_async);
        // if ($banbanSdk->isGrayUser($userId)) {
        $banbanSdk->userBlacklistAdd($userId, $blackUserId);
        // }

        $blackUserId = is_array($blackUserId) ? $blackUserId : array($blackUserId);
        $blackUserId = array_map('strval', $blackUserId);
        return $this->curl('/user/blacklist/add', array(
            'userId' => strval($userId),
            'blackUserId' => $blackUserId
        ));
    }
    public function userBlacklistRemove($userId, $blackUserId)
    {
        $banbanSdk = new SdkBanBanIm();
        $banbanSdk->useAsync($this->_use_async);
        // if ($banbanSdk->isGrayUser($userId)) {
        $banbanSdk->userBlacklistRemove($userId, $blackUserId);
        // }

        $blackUserId = is_array($blackUserId) ? $blackUserId : array($blackUserId);
        $blackUserId = array_map('strval', $blackUserId);
        return $this->curl('/user/blacklist/remove', array(
            'userId' => strval($userId),
            'blackUserId' => $blackUserId
        ));
    }

    /**
     *刷新用户信息 方法  说明：当您的用户昵称和头像变更时，您的 App Server 应该调用此接口刷新在融云侧保存的用户信息，以便融云发送推送消息的时候，能够正确显示用户信息
     * @param $userId   用户 Id，最大长度 32 字节。是用户在 App 中的唯一标识码，必须保证在同一个 App 内不重复，重复的用户 Id 将被当作是同一用户。（必传）
     * @param string $name  用户名称，最大长度 128 字节。用来在 Push 推送时，或者客户端没有提供用户信息时，显示用户的名称。
     * @param string $portraitUri   用户头像 URI，最大长度 1024 字节
     * @return mixed
     */
    public function userRefresh($userId, $name = '', $portraitUri = '', $async = false)
    {
        if (empty($userId)) {
            throw new Exception('用户 Id 不能为空');
        }
        if (empty($name)) {
            throw new Exception('用户名称不能为空');
        }
        if (empty($portraitUri)) {
            throw new Exception('用户头像 URI 不能为空');
        }
        return $this->curl('/user/refresh', array('userId' => $this->encodeUid($userId), 'name' => $name, 'portraitUri' => $portraitUri), $async);
    }

    /**
     * 获取 APP 内指定某天某小时内的所有会话消息记录的下载地址
     * @param $date 指定北京时间某天某小时，格式为：2014010101,表示：2014年1月1日凌晨1点。（必传）
     * @return json|xml
     */
    public function messageHistory($date)
    {
        if (empty($date)) {
            throw new Exception('时间不能为空');
        }
        return $this->curl('/message/history', array('date' => $date));
    }

    /**
     * 销毁聊天室
     * @param $chatroomId   要销毁的聊天室 Id。（必传）
     * @return json|xml
     */
    public function chatroomDestroy($chatroomId)
    {
        if (empty($chatroomId)) {
            throw new Exception('要销毁的聊天室 Id 不能为空');
        }
        return $this->curl('/chatroom/destroy', array('chatroomId' => $chatroomId));
    }

    /**
     * 删除 APP 内指定某天某小时内的所有会话消息记录
     * @param $date string 指定北京时间某天某小时，格式为2014010101,表示：2014年1月1日凌晨1点。（必传）
     * @return mixed
     */
    public function messageHistoryDelete($date)
    {
        if (empty($date)) {
            throw new Exception('时间 不能为空');
        }
        return $this->curl('/message/history/delete', array('date' => $date));
    }

    //删除图像
    public function imageDelete($image)
    {
        //https://api.cn.rong.io/image/delete.json
        return $this->curl('/image/delete', array('url' => $image));
    }

    public function recall($fromUserId, $conversationType, $targetId, $messageUID, $sentTime)
    {
        // 自研im
        $imBanban = new SdkBanBanIm();
        $imBanban->useAsync(true);
        return $imBanban->recall($fromUserId, $conversationType, $targetId, $messageUID, $sentTime);

        // 融云已废弃
        if ($conversationType == 'private') {
            $conversationTypeNumber = 1;
        } else {
            $conversationTypeNumber = 3;
        }
        return $this->curl(
            '/message/recall',
            array(
                'fromUserId' => $fromUserId,
                'conversationType' => $conversationTypeNumber,
                'targetId' => $targetId,
                'messageUID' => $messageUID,
                'sentTime' => $sentTime,
            )
        );
    }

    /**
     * 重写实现 http_build_query 提交实现(同名key)key=val1&key=val2
     * @param array $formData 数据数组
     * @param string $numericPrefix 数字索引时附加的Key前缀
     * @param string $argSeparator 参数分隔符(默认为&)
     * @param string $prefixKey Key 数组参数，实现同名方式调用接口
     * @return string
     */
    private function build_query($formData, $numericPrefix = '', $argSeparator = '&', $prefixKey = '')
    {
        $str = '';
        foreach ($formData as $key => $val) {
            if (!is_array($val)) {
                $str .= $argSeparator;
                if ($prefixKey === '') {
                    if (is_int($key)) {
                        $str .= $numericPrefix;
                    }
                    $str .= urlencode($key) . '=' . urlencode($val);
                } else {
                    $str .= urlencode($prefixKey) . '=' . urlencode($val);
                }
            } else {
                if ($prefixKey == '') {
                    $prefixKey .= $key;
                }
                if (is_array($val[0])) {
                    $arr = array();
                    $arr[$key] = $val[0];
                    $str .= $argSeparator . http_build_query($arr);
                } else {
                    $str .= $argSeparator . $this->build_query($val, $numericPrefix, $argSeparator, $prefixKey);
                }
                $prefixKey = '';
            }
        }
        return substr($str, strlen($argSeparator));
    }

    /**
     * 发起 server 请求
     * @param $action
     * @param $params
     * @param $httpHeader
     * @return mixed
     */
    public function curl($action, $params, $async = false, $contentType = 'urlencoded')
    {
        $isAsync = $this->_use_async || $async;
        if ($isAsync && substr(php_sapi_name(), 0, 3) == 'cli') {
            return $this->curlAsync($action, $params, $contentType);
        } else {
            return $this->curlSync($action, $params, $contentType);
        }
    }

    private function curlAsync($action, $params, $contentType = 'urlencoded')
    {
        $action = self::SERVERAPIURL . $action . '.' . $this->format;
        $num = 0;
        $post = $this->build_query($params);
        while ($num <= 10) {
            $num++;
            $header = $this->_configIm->createKeyValueHttpHeader($this->_env);
            $header['Content-Type'] = 'application/x-www-form-urlencoded';
            //$header['Content-Length'] = strval(strlen($post));
            $postData = array(
                'header' => $header,
                'action' => $action,
                'post' => $post,
            );
            $res = $this->httpRequest('http://'. Serv_Rong_Proxy_Name .'/', true, json_encode($postData));
            if (is_null($res) || empty($res) || !isset($res['code'])) {
                continue;
            }
            //检查返回的code数据
            if ($res['code'] == 200) {
                break;
            }
            //对于一些code，重试
            usleep(1000 * 100);
        }

        Helper::debugger()->warning("ImSdkHttpInfo::{$action}\t" . json_encode($res['code']));
        return $res;
    }

    private function curlSync($action, $params, $contentType = 'urlencoded')
    {
        $action = self::SERVERAPIURL . $action . '.' . $this->format;
        $header = $this->_configIm->createHttpHeader($this->_env);
        //增加一个重试机制
        $num = 0;
        $post = $this->build_query($params);
        while ($num <= 1) {
            $num++;
            //echo "Im send {$action} => {$post}\n";
            $res = $this->httpRequest($action, true, $post, $header);
            //对于网络异常或者不可能的情况重试
            if (is_null($res) || empty($res) || !isset($res['code'])) {
                continue;
            }
            //检查返回的code数据
            if ($res['code'] == 200) {
                break;
            }
            //对于一些code，重试
            if (in_array($res['code'], array(1008, 1050))) {
                usleep(1000 * 100);
                continue;
            } else {
                break;
            }
        }

        Helper::debugger()->warning("ImSdkHttpInfo::{$action}\t" . json_encode($res['code']));
        return $res;
    }
}
