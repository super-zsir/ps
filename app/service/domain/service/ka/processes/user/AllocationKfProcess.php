<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;


use Imee\Exception\Ka\UserException;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Models\Xsst\Ka\BmsKaUserListLog;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\User\AllocationKfContext;

class AllocationKfProcess
{
    /**
     * @var AllocationKfContext
     */
    private $context;

    /**
     * @var XsstKaOrganizationUser
     */
    private $kfModel = XsstKaOrganizationUser::class;

    /**
     * @var BmsKaUserList
     */
    private $kaUserModel = BmsKaUserList::class;

    /**
     * @var BmsKaUserListLog
     */
    private $kaUserLogModel = BmsKaUserListLog::class;

    public function __construct(AllocationKfContext $context)
    {
        $this->context         = $context;
    }

    public function handle()
    {
        $uids = $this->context->uid;
        $kfId = (int) $this->context->kfId;

        if (empty($uids)) {
            UserException::throwException(UserException::USER_SELECT_ERROR);
        }

        $uids = explode(',', $uids);

        if ($kfId > 0) {
            $check = $this->kfModel::getInfoByKfId($kfId);

            if (empty($check)) {
                UserException::throwException(UserException::COMMON_KF_NOT_EXIST);
            }
        }

        $now = time();

        if (count($uids) > 1) {
            $this->validationUids($uids);
        }

        foreach ($uids as $uid) {
            $info = $this->kaUserModel::findFirstByUid($uid);
            if (empty($info)) {
                continue;
            }
            $infoBak = clone $info;

            $gid = $info->gid;

            // 查询小号是否在超r列表
            $smallSupers = $this->kaUserModel::query()
                ->columns('uid,kf_id')
                ->where('gid = :gid:', ['gid' => $gid])
                ->execute()
                ->toArray();

            $smallSupers = array_column($smallSupers, null, 'uid');
            foreach ($smallSupers as $smallUid => $smallSuper) {
                if ($smallUid == $uid) {
                    $info->kf_id          = $kfId;
                    $info->kf_create_time = $now;
                    $extra['source']      = !empty($this->context->source) ? $this->context->source : '批量分配客服';
                    $extra['admin']       = !empty($this->context->adminUid) ? $this->context->adminUid : '0';
                    // 记录日志
                    if ($info->save()) {
                        $this->kaUserLogModel::saveLog($infoBak, $info, $extra);
                    }
                    continue;
                }

                // 没有绑定过的小号 就要绑定客服
                $smallInfo = $this->kaUserModel::findFirst([
                    'conditions' => 'uid = :uid:',
                    'bind'       => ['uid' => $smallUid]
                ]);

                if (!$smallInfo) {
                    continue;
                }

                $smallInfoBak = clone $smallInfo;

                $smallInfo->kf_id          = $kfId;
                $smallInfo->kf_create_time = $now;
                $extra['source']           = !empty($this->context->source) ? $this->context->source : '批量分配客服';
                $extra['admin']            = !empty($this->context->adminUid) ? $this->context->adminUid : '0';
                // 记录日志
                if ($smallInfo->save()) {
                    $this->kaUserLogModel::saveLog($smallInfoBak, $smallInfo, $extra);
                }
            }
        }
    }

    private function validationUids($uids)
    {
        // 批量分配客服 必须选择只看大号
        $users = $this->kaUserModel::find([
            'columns'    => 'uid,gid,big_id',
            'conditions' => 'uid IN ({uid:array})',
            'bind'       => ['uid' => $uids]
        ]);

        foreach ($users as $user) {
            if ($user->uid != $user->big_id) {
                UserException::throwException(UserException::USER_BATCH_KF_SELECT_BIG);
            }
        }
    }
}
