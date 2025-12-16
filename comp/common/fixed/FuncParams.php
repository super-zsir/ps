<?php
/**
 *
 * 方法参数化
 */

namespace Imee\Comp\Common\Fixed;

/**
 * Class FuncParams 方法传递参数时候用的，需要的直接在注释里加入就可以了
 *==============通用字段开始
 * @property $uid int uid
 * @property $appId int 应用id
 * @property $platform string 平台
 *==============通用字段结束
 * ==============亲密度方法参数开始
 * @property $sUid int xs_user_accompany_record表的suid字段
 * @property $tUid int xs_user_accompany_record表的tuid字段
 * @property $staySecs int xs_user_accompany_record表的stay_secs字段
 * @property $roomSignCount int xs_user_accompany_record表的room_sign_count字段
 * @property $respMessageCount int xs_user_accompany_record表的resp_message_count字段
 * @property $degree int xs_user_accompany_record表的degree字段
 * @property $state int xs_user_accompany_record表的state字段
 * @property $dateline int xs_user_accompany_record表的dateline字段
 * ==============亲密度方法参数结束
 * ==============首页tap开始@zeng.gang
 * @property $tapId int tab的id
 * @property $tabFilter string 子tab下的sub中的filter字段
 * ==============首页tap结束
 * =============房间相关字段@zeng.gang
 * @property  $rid int 房间的id
 * @property  $cid int live-game类型的房间需要一个cid
 * @property  $roomState int 房间的state字段
 * @property  $roomName string 房间的名字
 * @property $roomType string room的type
 * @property $roomToType string room的想去的type
 * @property $roomOldFactoryType string room的老的Factorytype
 * @property $roomTypes string room的types
 * @property $roomGame string room的game
 * @property $roomProperty string room的game
 * @property $roomDescription string room的description
 * @property $reception string room的reception
 * @property $paier string room的paier
 * @property $closeBox string room的close_box
 * @property $roomPrefix string room的prefix
 * @property $roomIcon string room的prefix
 * @property $roomObj XsChatroom room的game
 * @property $puzzleId string 创建海龟汤是需要的汤id
 * @property $subType string 玩法切换的类型
 * @property $roomPassword string 房间的密码
 * @property $roomPositionNum string 房间的密码
 * @property $roomFactoryType string 房间的工厂类型
 * ==============房间相关字段@zeng.gang
 *
 * *==============option/index接口的一些字段@zeng.gang=====================
 * @property $optionIndexVersion int option/index接口的option_index_version
 * @property $optionUsePartyStyle bool option/index 是否使用派对模式
 * ==============option/index接口的一些字段@zeng.gang=====================
 *
 *
 * *==============阿里云直接上传需要一些字段@zeng.gang=====================
 * @property $accessKeyId string 阿里云的oss的accessKeyId
 * @property $accessKeySecret string 阿里云的oss的accessKeySecret
 * @property $bucket string 阿里云的oss的bucketName
 * @property $endPoint string 阿里云的oss的endPoint，这个感觉是锚点的意思，文件落地的域名？
 * @property $dir string  可以理解为文件夹，也就是在bucket下的文件夹。根据自己需要来如：img，voice等等
 * @property $callbackUrl string  回调的地址以及参数
 * @property $fileUrl string  文件的前缀url地址
 * ==============option/index接口的一些字段@zeng.gang=====================
 *
 *==============剧本房相关的一些字段@zeng.gang 开始=====================
 * @property $juBenObj XsJubenGeneral  剧本的id
 * @property $juBenIsTwo int 剧本的id
 * @property $juBenVisit int 剧本的id
 * @property $juBenVersion int 剧本的id
 * @property $juBenIsMatch int 剧本的id
 * ==============剧本房相关的一些字段@zeng.gang  结束=====================
 *
 *==============admin后台需要的参数@linpei 开始=====================
 * @property $fid int factory id
 * @property $bid int 工会id
 * @property $roomSex int 性别
 * @property $displayRank int 排行榜
 * @property $switchingTime int 拍卖模板切换时间
 * @property $switchingStartTime string 拍卖模板切换开始时间
 * @property $switchingEndTime string 拍卖模板切换结束时间
 * @property $cplinkTime int 连连看切换时间
 * @property $cplinkStartTime string 连连看切换开始时间
 * @property $cplinkEndTime string 连连看切换结束时间
 * @property $theoneTime int 非诚勿扰切换时间
 * @property $theoneStartTime string 非诚勿扰切换开始时间
 * @property $theoneEndTime string 非诚勿扰切换结束时间
 * @property $grabmicTime int C位抢唱切换时间
 * @property $grabmicStartTime string C位抢唱切换开始时间
 * @property $grabmicEndTime string C位抢唱切换结束时间
 * @property XsUserIdcard $idcardObj  object  id card对象
 * @property $roomMode int 模式(自由上麦、抱人上麦)
 * @property $roomThem string 皮肤
 * @property $bicon string 大图
 * @property $roomDeleted int 聊天室状态
 * @property $roomNine int 麦位
 * @property $factory array 工厂数据
 * @property $roomWeight int 权重
 * @property $opType string create 创建 update更新
 * @property $roomTagId int 外显标签id
 * @property $roomSettlementChannel string 结算频道
 *==============admin后台需要的参数@linpei 结束=====================
 *
 */
class FuncParams implements \ArrayAccess
{

    public function __set($name, $value)
    {
        if ($name != 'properArr') {
            $this->$name = $value;
        }
    }

    public function toArray()
    {
        $data = array();
        foreach ($this as $k => $v) {
            $data[$k] = $v;
        }
        return $data;
    }

    public function toObject($params)
    {
        foreach ($params as $k => $item) {
            $this->{$k} = $item;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
}