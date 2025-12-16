<?php

namespace Imee\Controller\Operate\User;

use Imee\Controller\BaseController;
use Imee\Controller\Validation\Operate\User\UserVipValidation;
use Imee\Service\Operate\User\UserVipService;

class UservipController extends BaseController
{

    /**
     * @page uservip
     * @name 用户管理-用户列表-vip详情
     */
    public function mainAction()
    {
    }

    /**
     * @page  uservip
     * @point VIP列表
     */
    public function listAction()
    {
        $uid = intval($this->params['uid']);
        if (empty($uid)) {
            return $this->outputSuccess([], ['total' => 0]);
        }
        $data = UserVipService::getUserVipList($uid);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page  uservip
     * @point 编辑
     */
    public function modifyAction()
    {
        $c = $this->params['c'] ?? '';
        if ($c == 'check') {
            $result = UserVipService::checkVip7($this->params);
            if (isset($result['is_info']) && $result['is_info'] === true) {
                return $this->outputSuccess(['is_info' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            } elseif (isset($result['is_confirm']) && $result['is_confirm'] === true) {
                return $this->outputSuccess(['is_confirm' => true, 'confirm_text' => $result['msg'], 'width' => 700]);
            }

            return $this->outputSuccess(['is_confirm' => false]);
        }
        UserVipValidation::make()->validators($this->params);
        list($res, $msg) = UserVipService::modify($this->params);
        return $res ? $this->outputSuccess($msg) : $this->outputError(-1, $msg);
    }

    /**
     * @page  uservip
     * @point 日志
     */
    public function logAction()
    {
        $list = UserVipService::getUserVipLogList($this->params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }
}