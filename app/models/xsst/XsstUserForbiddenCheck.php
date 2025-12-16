<?php

namespace Imee\Models\Xsst;

class XsstUserForbiddenCheck extends BaseModel
{
    const STATUS_WAITING = 1;
    const STATUS_CHANGE = 2;
    const STATUS_DEBLOCKING = 3;
    const STATUS_UNDO = 4;

    public static $status_arr = [
        self::STATUS_WAITING => '待审核',
        self::STATUS_CHANGE => '已改封',
        self::STATUS_DEBLOCKING => '已解封',
        self::STATUS_UNDO => '不解封',  //实际就是对应着不解封
    ];

    /**
     * 核查结果
     * @param op_id  操作人
     * @param $uid 核查用户
     * @param $deleted 核查结果
     * @param $op_check 核查日志id ,xs_user_forbidden_log.id
     * @return  true
     */
    public static function userForbiddenModify($op_id, $uid, $log_id, $status, $reason = '')
    {
        //日志
        XsstUserForbiddenCheckLog::saveRows(array(
            'uid' => $uid,
            'status' => $status,
            'log_id' => $log_id,
            'dateline' => time(),
            'op_id' => $op_id,
        ));
        $rec = XsstUserForbiddenCheck::findFirst(array(
            "log_id = $log_id and uid = $uid "
        ));
        if ($rec && $rec->id) {
            $rec->status = $status;
            $rec->reason = $reason;
            $rec->save();
        } else {
            XsstUserForbiddenCheck::saveRows(array(
                'uid' => $uid,
                'status' => $status,
                'reason' => $reason,
                'log_id' => $log_id,
                'dateline' => time(),
            ));
        }
        return true;
    }

    public static function saveRows($rows)
    {
        $rec = new self();
        foreach ($rows as $k => $v) {
            $rec->{$k} = $v;
        }
        $rec->create_time = time();
        $rec->save();
        return true;
    }
}
