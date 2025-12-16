<?php
/**
 *物品发送表
 */

namespace Imee\Models\Xs;

use Imee\Models\Xsst\BmsCommoditySendReason;

class XsCommoditySend extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATE_WAIT = 0;
    const STATE_PASS = 1;
    const STATE_FAIL = 2;

    public static $state = [
        self::STATE_WAIT => '待审核',
        self::STATE_PASS => '审核通过',
        self::STATE_FAIL => '审核不通过',
    ];

    public static function uploadFields(): array
    {
        return [
            'uid'      => 'UID',
            'cid'      => '物品记录ID',
            'num'      => '数量',
            'exp_days' => '资格使用有效天数',
            'mark'     => '备注',
            'source'   => '发放来源',
        ];
    }

    public static function setFailToVerify(int $aid, int $admin): bool
    {
        if ($aid < 1 || $admin < 1) {
            return false;
        }
        $data = self::useMaster()->find([
            'columns'    => 'id',
            'conditions' => 'aid=:aid: AND state=:state:',
            'bind'       => ['aid' => $aid, 'state' => self::STATE_FAIL]
        ])->toArray();
        if (empty($data)) {
            return false;
        }
        $ids = array_column($data, 'id');
        $data = BmsCommoditySendReason::getByReason($ids);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $v) {
            $rec = self::useMaster()->findFirst([
                'conditions' => 'id=:id:',
                'bind'       => ['id' => $v['sid']]
            ]);
            //物品发放改待审核
            if ($rec->state == self::STATE_FAIL) {
                $rec->state = self::STATE_WAIT;
                $rec->verifyadmin = $admin;
                $rec->verifytime = time();
                $rec->save();
                BmsCommoditySendReason::cancelReason($v['sid']); //取消未通过原因
            }
        }
        return true;
    }
}