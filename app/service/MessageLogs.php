<?php

namespace Imee\Service;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisHash;
use Phalcon\Di;

class MessageLogs
{

    public static $_roomLogDir = "/home/msgs/messagelogs/";
    public static $_chatMessagesDir = "/home/msgs/chatmessagelogs/";
    public static $_statLogDir = "/home/msgs/statlogs/";
    public static $_roomUserIntoLogDir = "/home/msgs/roomuserintologs/";
    public static $_roomnewLogDir = "/home/msgs/roomnewlogs/";

    // 房间公屏消息取出执行写
    public static function roomLogs($res)
    {
        if (empty($res)) return;
        $sql = array();
        $strArr = array();

        foreach ($res as $val) {
            $data = $val['message'];
            if (isset($data['name']) && $data['name'] == 'room.refersh') continue;

            $message = '';
            if (isset($data['message'])) {
                $message = addslashes($data['message']);
            } else if (isset($data['content'])) {
                $message = addslashes($data['content']);
            }

            $type = 'normal';
            if (isset($data['extra'])) {
                if (is_string($data['extra'])) {
                    $extra = json_decode($data['extra'], true);
                } else if (is_array($data['extra'])) {
                    $extra = $data['extra'];
                } else {
                    echo "error extra\n";
                    print_r($val);
                    continue;
                }
                if (isset($extra['type'])) {
                    switch ($extra['type']) {
                        case 'package':
                        case 'system':
                            $type = $extra['type'];
                            break;

                        case 'notify':
                            $type = 'system';
                            break;

                        default:
                            $type = 'other';
                            break;
                    }
                }
                unset($extra['vip'], $extra['vip_new'], $extra['title'], $extra['online'], $extra['version'], $extra['top'], $extra['top_month'], $extra['top_week'], $extra['hotNum'], $extra['now'], $extra['emote_sender'], $extra['emote_position'], $extra['uids']);

                $extra = addslashes(json_encode($extra));
            } else {
                $extra = addslashes(json_encode(array()));
            }
            $sql[] = "('{$val['rid']}', '{$type}', '{$val['uid']}', '{$message}', '{$extra}', '{$val['dateline']}')";

            $strArr[] = array(
                "rid"      => $val['rid'],
                "type"     => $type,
                "uid"      => $val['uid'],
                "content"  => $message,
                "extra"    => $extra,
                "dateline" => $val['dateline']
            );
        }

        if (!empty($sql)) {
            // Di::getDefault()->getShared(\XssBaseModel::SCHEMA)->execute("insert into xss_room_log (rid, type, uid, content, extra, dateline) values " . implode(", ", $sql));
            Di::getDefault()->getShared('xssdb')->execute("insert into xss_room_log_new (rid, type, uid, content, extra, dateline) values " . implode(", ", $sql));
        }

        MessageLogs::roomLogsHistory($strArr);

        echo "insert rows roomLogs " . count($res) . "\n";
    }

    // 房间公屏消息写文件
    public static function roomLogsHistory($res)
    {
        $strArr = array();

        foreach ($res as $val) {
            $dateh = date("Y-m-d.H", $val['dateline']);
            if (!isset($strArr[$dateh])) {
                $strArr[$dateh] = array();
            }
            $strArr[$dateh][] = array($val['rid'], $val['type'], $val['uid'], $val['content'], $val['extra'], $val['dateline']);
        }

        foreach ($strArr as $d => $dv) {
            $fileName = self::$_roomLogDir . $d . ".log";
            $myfile = fopen($fileName, "a");
            foreach ($dv as $dvv) {
                fwrite($myfile, implode("\001", $dvv) . "\n");
            }
            fclose($myfile);
        }

        return true;
    }

    // 一对一聊天记录存入redis
    public static function chatMessageIntoRedis($res)
    {
        $redis = new RedisHash(RedisBase::REDIS_CACHE, false);
        $key = 'Xs.Chat.Message.Log';
        $redis->rPush($key, serialize($res));
    }

    // 一对一聊天写文件
    public static function chatMessagesHistory($res)
    {
        $strArr = array();

        foreach ($res as $val) {
            $dateh = date("Y-m-d.H", $val['dateline']);
            if (!isset($strArr[$dateh])) {
                $strArr[$dateh] = array();
            }
            $strArr[$dateh][] = array($val['sid'], $val['from_user_id'], $val['to_user_id'], $val['object_name'], $val['content'], $val['dateline'], $val['channel_type'], $val['msg_uid'], $val['target_id'], $val['source']);
        }

        foreach ($strArr as $d => $dv) {
            $fileName = self::$_chatMessagesDir . $d . ".log";
            $myfile = fopen($fileName, "a");
            foreach ($dv as $dvv) {
                fwrite($myfile, implode("\001", $dvv) . "\n");
            }
            fclose($myfile);
        }

        return true;
    }

    // 新用户行为消息取出执行写
    public static function statLogs($res)
    {
        if (empty($res)) return;
        $sql = array();
        foreach ($res as $val) {
            $sql[] = "('{$val['mac']}', '{$val['uid']}', '{$val['action']}', '{$val['version']}', '{$val['platform']}', '{$val['jversion']}', '{$val['channel']}', '{$val['dateline']}')";
        }
        $conn = Di::getDefault()->getShared('xssdb');
        $conn->execute("insert into xs_statistics (mac, uid, action, version, platform, jversion, channel, dateline) values " . implode(", ", $sql));

        MessageLogs::statHistory($res);
        echo "insert rows statLogs " . count($res) . "\n";
    }

    // 新用户行为写文件
    public static function statHistory($res)
    {
        $strArr = array();

        foreach ($res as $val) {
            $dateh = date("Y-m-d.H", $val['dateline']);
            if (!isset($strArr[$dateh])) {
                $strArr[$dateh] = array();
            }
            $strArr[$dateh][] = array($val['mac'], $val['uid'], $val['action'], $val['dateline'], $val['version'], $val['platform'], $val['jversion'], $val['channel']);
        }

        foreach ($strArr as $d => $dv) {
            $fileName = self::$_statLogDir . $d . ".log";
            $myfile = fopen($fileName, "a");
            foreach ($dv as $dvv) {
                fwrite($myfile, implode("\001", $dvv) . "\n");
            }
            fclose($myfile);
        }

        return true;
    }

    // 用户进入房间记入redis
    public static function roomUserIntoLogIntoRedis($res)
    {
        $redis = new RedisHash(RedisBase::REDIS_CACHE, false);
        $key = 'Xs.Room.UserInto.Log';
        $redis->rPush($key, serialize($res));
    }

    // 取出redis数据，塞入数据库
    public static function roomUserIntoLogIntoMysql($res)
    {
        if (empty($res)) return;
        $sql = array();
        foreach ($res as $val) {
            $sql[] = "('{$val['uid']}', '{$val['rid']}', '{$val['tp']}', '{$val['dateline']}')";
        }
        $conn = Di::getDefault()->getShared("xsstdb");
        // $conn->execute("insert into xsst_room_userinto_log (uid, rid, tp, dateline) values " . implode(", ", $sql));
        $conn->execute("insert into xsst_room_userinto_lognew (uid, rid, tp, dateline) values " . implode(", ", $sql));

        MessageLogs::roomUserIntoLogIntoFile($res);
        echo "insert rows roomUserIntoLog " . count($res) . "\n";
    }

    // 取出redis数据，写入文件
    public static function roomUserIntoLogIntoFile($res)
    {
        $strArr = array();

        foreach ($res as $val) {
            $dateh = date("Y-m-d.H", $val['dateline']);
            if (!isset($strArr[$dateh])) {
                $strArr[$dateh] = array();
            }
            $strArr[$dateh][] = array($val['uid'], $val['rid'], $val['tp'], $val['dateline']);
        }

        foreach ($strArr as $d => $dv) {
            $fileName = self::$_roomUserIntoLogDir . $d . ".log";
            $myfile = fopen($fileName, "a");
            foreach ($dv as $dvv) {
                fwrite($myfile, implode("\001", $dvv) . "\n");
            }
            fclose($myfile);
        }

        return true;
    }
}