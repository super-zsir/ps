<?php

namespace Imee\Service\Domain\Service\Ka\Processes\User;

use Imee\Exception\Ka\UserException;
use Imee\Models\Xsst\Ka\BmsKaUserList;
use Imee\Models\Xsst\Ka\BmsKaUserListLog;
use Imee\Service\Domain\Context\Ka\User\BuildAlStatusContext;
use Imee\Service\Lesscode\Exception\CommonException;

class BuildAlStatusProcess
{
    /**
     * @var BuildAlStatusContext
     */
    private $context;

    /**
     * @var BmsKaUserList
     */
    private $kaUser = BmsKaUserList::class;

    /**
     * @var BmsKaUserListLog
     */
    private $kaUserLog = BmsKaUserListLog::class;

    public function __construct(BuildAlStatusContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $extra = ['admin' => $this->context->adminUid];

        if (!empty($this->context->source)) {
            $extra['source'] = $this->context->source;
        }

        if (empty($this->context->uid)) {
            UserException::throwException(UserException::ILLEGAL_ERROR);
        }

        if (!empty($this->context->buildAccount) && empty($this->context->friendDate)) {
            UserException::throwException(UserException::USER_BUILD_AL_STATUS_FRIEND_DATE);
        } else {
            $extra['source'] = 'relieve';
        }

        $info    = $this->kaUser::findFirstByUid($this->context->uid);
        $infoBak = clone $info;

        $gid = $info->gid;

        if (empty($info)) {
            UserException::throwException(UserException::ILLEGAL_ERROR);
        }

        if (empty($this->context->friendDate)) {
            [$code, $msg] = CommonException::FILTER_NO_TEXT;
            throw new CommonException(sprintf($msg, '建联时间'), $code);
        }

        if (empty($this->context->buildAlType)) {
            [$code, $msg] = CommonException::FILTER_NO_TEXT;
            throw new CommonException(sprintf($msg, '建联类型'), $code);
        }

        $buildAlType = (int) $this->context->buildAlType;

        // 不填写微信号 即可删除建联状态
        if (empty($this->context->buildAccount)) {
            $buildAlStatus = 0;
        } else {
            $buildAlStatus = 1;
        }

        $friendDate = strtotime($this->context->friendDate);

        $info->build_al_type   = $buildAlType;
        $info->build_al_status = $buildAlStatus;
        $info->build_account = $this->context->buildAccount;
        $info->friend_date     = !empty($friendDate) && is_numeric($friendDate) ? $friendDate : time();
        $info->update_time     = time();

        $smallSupers = $this->kaUser::query()
            ->where('gid = :gid:', ['gid' => $gid])
            ->execute();

        if ($smallSupers->toArray()) {
            foreach ($smallSupers as $user) {
                if ($user->uid == $this->context->uid) {
                    continue;
                }
                $userBak = clone $user;
                $user->build_al_status = empty($this->context->buildAccount) ? 0 : 2;
                $user->friend_date = !empty($this->context->friendDate) ? strtotime($this->context->friendDate) : 0;
                $user->build_account    = $this->context->buildAccount;
                if ($user->getChangedFields()) {
                    if ($user->save()) {
                        $this->kaUserLog::saveLog($userBak, $user, $extra);
                    }
                }
            }
        }
        if ($info->save()) {
            $this->kaUserLog::saveLog($infoBak, $info, $extra);
        }
        return;
    }
}