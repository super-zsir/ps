<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Models\Bms\XsstVerifyTimeKanban;
use Imee\Service\Domain\Service\Csms\Context\Databoard\TimeBoardContext;

/**
 * 业务数据看板 - 分时
 */
class TimeBoardProcess
{
    /**
     * @param TimeBoardContext $context
     * @return array
     */
    public function getTimeBoard(TimeBoardContext $context)
    {
        $condition = array(
            'dateline' => $context->date ? strtotime($context->date) : strtotime('yesterday'),
        );
        $list = XsstVerifyTimeKanban::handleList($condition);
        if ($list) {
            $countArr = array(
                'action' => '总计',
                'dateline' => date('Y-m-d', $condition['dateline']),
                'zero' => array(),
                'one' => array(),
                'two' => array(),
                'three' => array(),
                'four' => array(),
                'five' => array(),
                'six' => array(),
                'seven' => array(),
                'eight' => array(),
                'nine' => array(),
                'ten' => array(),
                'eleven' => array(),
                'twelve' => array(),
                'thirteen' => array(),
                'fourteen' => array(),
                'fifteen' => array(),
                'sixteen' => array(),
                'seventeen' => array(),
                'eighteen' => array(),
                'nineteen' => array(),
                'twenty' => array(),
                'twenty1' => array(),
                'twenty2' => array(),
                'twenty3' => array()
            );
            foreach ($list as &$v) {
                if ($v['table'] == 'text') {
                    if ($v['action'] == 'tmp_icon') {
                        $v['action'] = '用户头像（文本）';
                    } elseif ($v['action'] == 'nickname') {
                        $v['action'] = '用户昵称/签名（文本）';
                    } elseif ($v['action'] == 'xs_user_photos') {
                        $v['action'] = '用户形象照（文本）';
                    } elseif ($v['action'] == 'xs_chatroom') {
                        $v['action'] = '房间名字/公告（文本）';
                    } elseif ($v['action'] == 'xs_group') {
                        $v['action'] = '群组名字（文本）';
                    } elseif ($v['action'] == 'xs_fleet') {
                        $v['action'] = '家族标题/介绍（文本）';
                    } elseif ($v['action'] == 'xs_order_vote') {
                        $v['action'] = '订单评论（文本）';
                    } elseif ($v['action'] == 'xs_chat_message') {
                        $v['action'] = '私聊/群聊（文本）';
                    } elseif ($v['action'] == 'xs_welcome_text') {
                        $v['action'] = '迎新招呼（文本）';
                    } elseif ($v['action'] == 'xs_fleet_icon') {
                        $v['action'] = '家族封面（文本）';
                    } elseif ($v['action'] == 'xs_marry_relation') {
                        $v['action'] = '情侣小窝封面图片（文本）';
                    } elseif ($v['action'] == 'xs_wedding_album') {
                        $v['action'] = '婚礼相册（文本）';
                    } elseif ($v['action'] == 'xs_live_config') {
                        $v['action'] = '粉丝牌（文本）';
                    } elseif ($v['action'] == 'xs_marry_message') {
                        $v['action'] = '婚礼留言板（文本）';
                    }
                } elseif ($v['table'] == 'machine') {
                    if ($v['action'] == 'tmp_icon') {
                        $v['action'] = '用户头像（机审）';
                    } elseif ($v['action'] == 'nickname') {
                        $v['action'] = '用户昵称/签名（机审）';
                    } elseif ($v['action'] == 'xs_user_photos') {
                        $v['action'] = '用户形象照（机审）';
                    } elseif ($v['action'] == 'xs_chatroom') {
                        $v['action'] = '房间名字/公告（机审）';
                    } elseif ($v['action'] == 'xs_group') {
                        $v['action'] = '群组名字（机审）';
                    } elseif ($v['action'] == 'xs_fleet') {
                        $v['action'] = '家族标题/介绍（机审）';
                    } elseif ($v['action'] == 'xs_order_vote') {
                        $v['action'] = '订单评论（机审）';
                    } elseif ($v['action'] == 'xs_chat_message') {
                        $v['action'] = '私聊/群聊（机审）';
                    } elseif ($v['action'] == 'xs_fleet_icon') {
                        $v['action'] = '家族封面（机审）';
                    }
                } elseif ($v['table'] == 'friend_card') {
                    $v['action'] = '声音审核';
                } elseif ($v['table'] == 'screen_image') {
                    $v['action'] = '房间公屏图片';
                } elseif ($v['table'] == 'live_config') {
                    $v['action'] = '粉丝牌';
                } elseif ($v['table'] == 'circle') {
                    if ($v['action'] == 'circle') {
                        $v['action'] = '朋友圈审核';
                    } elseif ($v['action'] == 'comment') {
                        $v['action'] = '朋友圈评论审核';
                    } elseif ($v['action'] == 'report') {
                        $v['action'] = '朋友圈举报审核';
                    }
                } elseif ($v['table'] == 'god') {
                    $v['action'] = '大神技能';
                } elseif ($v['table'] == 'video_verify') {
                    $v['action'] = '大神视频';
                } elseif ($v['table'] == 'valentine_verify') {
                    $v['action'] = '表白墙审核';
                } elseif ($v['table'] == 'ability_audit_verify') {
                    $v['action'] = '能力类型审核';
                } elseif ($v['table'] == 'user_ability_verify') {
                    $v['action'] = '用户能力审核';
                } elseif ($v['table'] == 'wedding_album_verify') {
                    $v['action'] = '婚礼相册审核';
                } elseif ($v['table'] == 'game_card_verify') {
                    $v['action'] = '冲鸭游戏卡审核';
                } elseif ($v['table'] == 'user_tag_verify') {
                    $v['action'] = '标签审核';
                } elseif ($v['table'] == 'bbu_union_verify') {
                    $v['action'] = '联盟审核';
                } elseif ($v['table'] == 'grabmic_song_verify') {
                    $v['action'] = 'C位抢唱';
                }

                $v['dateline'] = date('Y-m-d', $v['dateline']);
                $dataArr = json_decode($v['content'], true);
                $countArr['zero']['audited'] += $dataArr['zero']['audited'];
                $countArr['zero']['incoming'] += $dataArr['zero']['incoming'];
                $v['zero'] = $dataArr['zero']['audited'].'/'.$dataArr['zero']['incoming'];
                $countArr['one']['audited'] += $dataArr['one']['audited'];
                $countArr['one']['incoming'] += $dataArr['one']['incoming'];
                $v['one'] = $dataArr['one']['audited'].'/'.$dataArr['one']['incoming'];
                $countArr['two']['audited'] += $dataArr['two']['audited'];
                $countArr['two']['incoming'] += $dataArr['two']['incoming'];
                $v['two'] = $dataArr['two']['audited'].'/'.$dataArr['two']['incoming'];
                $countArr['three']['audited'] += $dataArr['three']['audited'];
                $countArr['three']['incoming'] += $dataArr['three']['incoming'];
                $v['three'] = $dataArr['three']['audited'].'/'.$dataArr['three']['incoming'];
                $countArr['four']['audited'] += $dataArr['four']['audited'];
                $countArr['four']['incoming'] += $dataArr['four']['incoming'];
                $v['four'] = $dataArr['four']['audited'].'/'.$dataArr['four']['incoming'];
                $countArr['five']['audited'] += $dataArr['five']['audited'];
                $countArr['five']['incoming'] += $dataArr['five']['incoming'];
                $v['five'] = $dataArr['five']['audited'].'/'.$dataArr['five']['incoming'];
                $countArr['six']['audited'] += $dataArr['six']['audited'];
                $countArr['six']['incoming'] += $dataArr['six']['incoming'];
                $v['six'] = $dataArr['six']['audited'].'/'.$dataArr['six']['incoming'];
                $countArr['seven']['audited'] += $dataArr['seven']['audited'];
                $countArr['seven']['incoming'] += $dataArr['seven']['incoming'];
                $v['seven'] = $dataArr['seven']['audited'].'/'.$dataArr['seven']['incoming'];
                $countArr['eight']['audited'] += $dataArr['eight']['audited'];
                $countArr['eight']['incoming'] += $dataArr['eight']['incoming'];
                $v['eight'] = $dataArr['eight']['audited'].'/'.$dataArr['eight']['incoming'];
                $countArr['nine']['audited'] += $dataArr['nine']['audited'];
                $countArr['nine']['incoming'] += $dataArr['nine']['incoming'];
                $v['nine'] = $dataArr['nine']['audited'].'/'.$dataArr['nine']['incoming'];
                $countArr['ten']['audited'] += $dataArr['ten']['audited'];
                $countArr['ten']['incoming'] += $dataArr['ten']['incoming'];
                $v['ten'] = $dataArr['ten']['audited'].'/'.$dataArr['ten']['incoming'];
                $countArr['eleven']['audited'] += $dataArr['eleven']['audited'];
                $countArr['eleven']['incoming'] += $dataArr['eleven']['incoming'];
                $v['eleven'] = $dataArr['eleven']['audited'].'/'.$dataArr['eleven']['incoming'];
                $countArr['twelve']['audited'] += $dataArr['twelve']['audited'];
                $countArr['twelve']['incoming'] += $dataArr['twelve']['incoming'];
                $v['twelve'] = $dataArr['twelve']['audited'].'/'.$dataArr['twelve']['incoming'];
                $countArr['thirteen']['audited'] += $dataArr['thirteen']['audited'];
                $countArr['thirteen']['incoming'] += $dataArr['thirteen']['incoming'];
                $v['thirteen'] = $dataArr['thirteen']['audited'].'/'.$dataArr['thirteen']['incoming'];
                $countArr['fourteen']['audited'] += $dataArr['fourteen']['audited'];
                $countArr['fourteen']['incoming'] += $dataArr['fourteen']['incoming'];
                $v['fourteen'] = $dataArr['fourteen']['audited'].'/'.$dataArr['fourteen']['incoming'];
                $countArr['fifteen']['audited'] += $dataArr['fifteen']['audited'];
                $countArr['fifteen']['incoming'] += $dataArr['fifteen']['incoming'];
                $v['fifteen'] = $dataArr['fifteen']['audited'].'/'.$dataArr['fifteen']['incoming'];
                $countArr['sixteen']['audited'] += $dataArr['sixteen']['audited'];
                $countArr['sixteen']['incoming'] += $dataArr['sixteen']['incoming'];
                $v['sixteen'] = $dataArr['sixteen']['audited'].'/'.$dataArr['sixteen']['incoming'];
                $countArr['seventeen']['audited'] += $dataArr['seventeen']['audited'];
                $countArr['seventeen']['incoming'] += $dataArr['seventeen']['incoming'];
                $v['seventeen'] = $dataArr['seventeen']['audited'].'/'.$dataArr['seventeen']['incoming'];
                $countArr['eighteen']['audited'] += $dataArr['eighteen']['audited'];
                $countArr['eighteen']['incoming'] += $dataArr['eighteen']['incoming'];
                $v['eighteen'] = $dataArr['eighteen']['audited'].'/'.$dataArr['eighteen']['incoming'];
                $countArr['nineteen']['audited'] += $dataArr['nineteen']['audited'];
                $countArr['nineteen']['incoming'] += $dataArr['nineteen']['incoming'];
                $v['nineteen'] = $dataArr['nineteen']['audited'].'/'.$dataArr['nineteen']['incoming'];
                $countArr['twenty']['audited'] += $dataArr['twenty']['audited'];
                $countArr['twenty']['incoming'] += $dataArr['twenty']['incoming'];
                $v['twenty'] = $dataArr['twenty']['audited'].'/'.$dataArr['twenty']['incoming'];
                $countArr['twenty1']['audited'] += $dataArr['twenty1']['audited'];
                $countArr['twenty1']['incoming'] += $dataArr['twenty1']['incoming'];
                $v['twenty1'] = $dataArr['twenty1']['audited'].'/'.$dataArr['twenty1']['incoming'];
                $countArr['twenty2']['audited'] += $dataArr['twenty2']['audited'];
                $countArr['twenty2']['incoming'] += $dataArr['twenty2']['incoming'];
                $v['twenty2'] = $dataArr['twenty2']['audited'].'/'.$dataArr['twenty2']['incoming'];
                $countArr['twenty3']['audited'] += $dataArr['twenty3']['audited'];
                $countArr['twenty3']['incoming'] += $dataArr['twenty3']['incoming'];
                $v['twenty3'] = $dataArr['twenty3']['audited'].'/'.$dataArr['twenty3']['incoming'];
            }

            $countArr['zero'] = $countArr['zero']['audited'].'/'.$countArr['zero']['incoming'];
            $countArr['one'] = $countArr['one']['audited'].'/'.$countArr['one']['incoming'];
            $countArr['two'] = $countArr['two']['audited'].'/'.$countArr['two']['incoming'];
            $countArr['three'] = $countArr['three']['audited'].'/'.$countArr['three']['incoming'];
            $countArr['four'] = $countArr['four']['audited'].'/'.$countArr['four']['incoming'];
            $countArr['five'] = $countArr['five']['audited'].'/'.$countArr['five']['incoming'];
            $countArr['six'] = $countArr['six']['audited'].'/'.$countArr['six']['incoming'];
            $countArr['seven'] = $countArr['seven']['audited'].'/'.$countArr['seven']['incoming'];
            $countArr['eight'] = $countArr['eight']['audited'].'/'.$countArr['eight']['incoming'];
            $countArr['nine'] = $countArr['nine']['audited'].'/'.$countArr['nine']['incoming'];
            $countArr['ten'] = $countArr['ten']['audited'].'/'.$countArr['ten']['incoming'];
            $countArr['eleven'] = $countArr['eleven']['audited'].'/'.$countArr['eleven']['incoming'];
            $countArr['twelve'] = $countArr['twelve']['audited'].'/'.$countArr['twelve']['incoming'];
            $countArr['thirteen'] = $countArr['thirteen']['audited'].'/'.$countArr['thirteen']['incoming'];
            $countArr['fourteen'] = $countArr['fourteen']['audited'].'/'.$countArr['fourteen']['incoming'];
            $countArr['fifteen'] = $countArr['fifteen']['audited'].'/'.$countArr['fifteen']['incoming'];
            $countArr['sixteen'] = $countArr['sixteen']['audited'].'/'.$countArr['sixteen']['incoming'];
            $countArr['seventeen'] = $countArr['seventeen']['audited'].'/'.$countArr['seventeen']['incoming'];
            $countArr['eighteen'] = $countArr['eighteen']['audited'].'/'.$countArr['eighteen']['incoming'];
            $countArr['nineteen'] = $countArr['nineteen']['audited'].'/'.$countArr['nineteen']['incoming'];
            $countArr['twenty'] = $countArr['twenty']['audited'].'/'.$countArr['twenty']['incoming'];
            $countArr['twenty1'] = $countArr['twenty1']['audited'].'/'.$countArr['twenty1']['incoming'];
            $countArr['twenty2'] = $countArr['twenty2']['audited'].'/'.$countArr['twenty2']['incoming'];
            $countArr['twenty3'] = $countArr['twenty3']['audited'].'/'.$countArr['twenty3']['incoming'];
            array_unshift($list, $countArr);
        }
        $total = count($list);
        $list = array_slice($list, $context->offset, $context->limit);
        return ['data' => $list, 'total' => $total];
    }
}
