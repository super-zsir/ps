<?php

namespace Imee\Service\Domain\Service\Pretty\Processes\User;

use Imee\Service\Domain\Context\Pretty\User\ModifyContext;
use Imee\Service\Helper;
use Imee\Models\Xs\XsUserPretty;
use Imee\Models\Xs\XsUserIndex;
use Imee\Models\xsst\XsstUserPrettyExtend;

use Imee\Exception\Operate\PrettyUserException;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Rpc\PsService;
use Imee\Exception\ApiException;
use Imee\Models\Xsst\BmsOperateHistory;

/**
 * 列表
 */
class CreateOrModifyProcess
{
    use UserInfoTrait;
    private $context;
    private $model;
    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    private function isPrettyUid(string $prettyUid): bool
    {
        if (is_numeric($prettyUid)) {
            return !!preg_match('/^[0-9]{0,9}$/', $prettyUid);
        } else {
            return strlen($prettyUid) <= 50;
        }
    }

    private function verify()
    {
        $prettyUid = trim($this->context->prettyUid);
        //过期时间
        if (strtotime($this->context->expireTime) <= time()) {
            PrettyUserException::throwException(PrettyUserException::EXPIRE_TIME_ERROR);
        }

        //验证靓号格式
        if (!$this->isPrettyUid($prettyUid)) {
            PrettyUserException::throwException(PrettyUserException::PRETTYUSER_RULE_ERROR);
        }
        if (mb_strlen($prettyUid, 'UTF-8') <= XsUserPretty::PRETTY_LENGTH && !XsUserPretty::hasLengthPurview()) {
            throw new ApiException(ApiException::MSG_ERROR, '你需要申请【1位&2位数靓号】权限，才能发放对应靓号');
        }

        if (empty($this->context->id) && Helper::hasEmojis($this->context->prettyUid)) {
            PrettyUserException::throwException(PrettyUserException::PRETTYUSER_EMOJI_ERROR);
        }

        //用户是否存在
        if ($this->context->uid) {
            $userMap = $this->getUserInfoModel([$this->context->uid])->handle();
            if (empty($userMap)) {
                PrettyUserException::throwException(PrettyUserException::USER_DATA_NODEXIST_ERROR);
            }
        }

        if (is_numeric($this->context->prettyUid)) {
            $userMap = $this->getUserInfoModel([$this->context->prettyUid])->handle();
            if (!empty($userMap)) {
                PrettyUserException::throwException(PrettyUserException::PRETTYUSER_EQ_UID_ERROR);
            }

            $userInfo = XsUserIndex::findFirst([
                'columns' => 'uid',
                'order' => 'uid DESC',
            ]);
            if ($userInfo->uid < $this->context->prettyUid) {
                PrettyUserException::throwException(PrettyUserException::PRETTYUSER_MORE_THAN_MAX_UID_ERROR);
            }
        }

        $uid = $this->context->uid;
        if ($this->context->id) {
            $this->model = XsUserPretty::findFirst([
                'conditions' => 'id = :id:',
                'bind' => [
                    'id' => $this->context->id,
                ],
            ]);

            if (empty($this->model) || $this->model->uid != $this->context->uid) {
                //报错
                PrettyUserException::throwException(PrettyUserException::DATA_NOEXIST_ERROR);
            }
            $uid = $this->model->uid;

            if ($this->model->expire_time <= time()) {
                PrettyUserException::throwException(PrettyUserException::PRETTYUSER_HAS_EXPIRE_ERROR);
            }

            if ($this->model->pretty_uid != $this->context->prettyUid ||
                ($this->model->pretty_uid == $this->context->prettyUid && strtotime($this->context->expireTime) > $this->model->expire_time)) {
                if (Helper::hasEmojis($this->context->prettyUid)) {
                    PrettyUserException::throwException(PrettyUserException::PRETTYUSER_EMOJI_ERROR);
                }
            }
        }

        $userExistPrettyModel = XsUserPretty::findFirst([
            'conditions' => 'uid = :uid: AND expire_time>:now:',
            'bind' => array(
                'uid' => $uid,
                'now' => time()
            )
        ]);
        if (($this->context->id && $userExistPrettyModel && $userExistPrettyModel->id != $this->context->id) || (
            !$this->context->id && $userExistPrettyModel
        )) {
            PrettyUserException::throwException(PrettyUserException::USER_HAS_PRETTYUSER_ERROR);
        }

        $existPrettyModel = XsUserPretty::findFirst([
            'conditions' => 'pretty_uid = :pretty_uid: AND expire_time>:now:',
            'bind' => array(
                'pretty_uid' => $this->context->prettyUid,
                'now' => time()
            )
        ]);

        if (($this->context->id && $existPrettyModel && $existPrettyModel->id != $this->context->id) || (
            !$this->context->id && $existPrettyModel
        )) {
            PrettyUserException::throwException(PrettyUserException::PRETTYUSER_HAS_USED_ERROR);
        }

        return;
    }

    public function handle()
    {
        $this->verify();
        $id = 0;
        if (!$this->context->id) {
            $data = [
                'uid' => (int)$this->context->uid,
                'pretty_uid' => $this->context->prettyUid,
                'expire_time' => (int)strtotime($this->context->expireTime),
            ];
            [$res, $msg, $id] = (new PsService())->givePrettyUid($data);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
        } else {
            $data = [
                'id' => (int)$this->context->id,
                'pretty_uid' => $this->context->prettyUid,
                'expire_time' => (int)strtotime($this->context->expireTime),
            ];
            [$res, $msg] = (new PsService())->givePrettyUidModify($data);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }
            $id = $this->context->id;
        }

        $extend = XsstUserPrettyExtend::findFirst($id);
        if ($extend) {
            $extend->dateline = time();
            $extend->admin = Helper::getSystemUid();
            $extend->mark = $this->context->mark ??'';
            $extend->save();
        } else {
            $extend = new XsstUserPrettyExtend();
            $extend->pid = $id;
            $extend->mark = $this->context->mark ??'';
            $extend->admin = Helper::getSystemUid();
            $extend->dateline = time();
            $extend->save();
        }

        $data['mark'] = $this->context->mark ??'';

        $data['expire_time'] = date('Y-m-d H:i:s', $data['expire_time']);
        BmsOperateHistory::insertLog(
            BmsOperateHistory::PRETTY_NUM,
            $id,
            $data,
            Helper::getSystemUid()
        );
    }
}
