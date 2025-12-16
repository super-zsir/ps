<?php

namespace Imee\Controller\Operate\User;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Controller\BaseController;
use Imee\Export\Operate\User\PayHistoryExport;
use Imee\Export\Operate\User\PayUserHistoryExport;
use Imee\Export\Operate\User\UserListExport;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Operate\User\UserListService;

class UserController extends BaseController
{
    /** @var UserListService */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new UserListService();
    }

    /**
     * @page user
     * @name 用户管理-用户列表
     */
    public function mainAction()
    {
    }


    /**
     * @page  user
     * @point 用户列表
     */
    public function listAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';
        switch ($c) {
            case 'options':
                return $this->outputSuccess($this->service->getOptions($params));
            case 'price_level_log':
                $data = $this->service->getPriceLevelLog($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
            case 'user_forbidden_duration':
                return $this->outputSuccess($this->service->getUserForbiddenDuration($params));
            case 'user_forbidden_log':
                $data = $this->service->getUserForbiddenLog($params);
                return $this->outputSuccess($data['data'] ?? [], [
                    'total'     => $data['total'] ?? 0,
                    'godReason' => $data['god_reason'] ?? [],
                    'reason'    => $data['reason'] ?? [],
                ]);
            case 'lang_area_log':
                $data = $this->service->getLangAndAreaLog($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);

            case 'user_punish_history'://罚款历史
                $data = $this->service->userPunishHistory($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);

            case 'user_review_mod_log':
                $data = $this->service->getUserReviewModLog($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);

            default:
                $data = $this->service->getListAndTotal($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
        }
    }

    /**
     * @page  user
     * @point 导出
     */
    public function exportAction()
    {
        $condition = UserListService::getConditions($this->params);
        if (empty($condition)) {
            return $this->outputError(-1, '没有符合条件的数据');
        }
        $joinCondition = UserListService::getJoinCondition($this->params);
        $total = XsUserProfile::getJoinCount($condition, $joinCondition);

        if ($total > 100000) {
            return $this->outputError(-1, '最多只能导出10万条记录:' . $total);
        }

        ExportService::addTask($this->uid, 'user_list.csv', [UserListExport::class, 'export'], $this->params, '用户列表导出');
        return $this->outputSuccess();
    }

    /**
     * @page  user
     * @point 批量清除令牌
     */
    public function userResetTokenAction()
    {
        list($flg, $rec) = $this->service->userResetToken($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 批量昵称设置为无效
     */
    public function userResetNameAction()
    {
        list($flg, $rec) = $this->service->userResetName($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 批量昵称替换
     */
    public function userReplaceNameAction()
    {
        list($flg, $rec) = $this->service->userReplaceName($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 批量签名设置为无效
     */
    public function userResetSignAction()
    {
        list($flg, $rec) = $this->service->userResetSign($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


    /**
     * @page  user
     * @point 修改用户地区
     */
    public function updateUserAreaAction()
    {
        list($flg, $rec) = $this->service->updateUserArea($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 修改用户语言
     */
    public function updateUserLanguageAction()
    {
        list($flg, $rec) = $this->service->updateUserLanguage($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 修改用户大区
     */
    public function updateUserBigAreaAction()
    {
        list($flg, $rec) = $this->service->updateUserBigArea($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 修改财富等级
     */
    public function priceLevelAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';
        switch ($c) {
            case 'log':
                $data = $this->service->getPriceLevelLog($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
            default:
                list($flg, $rec) = $this->service->priceLevel($this->params);
                return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
        }
    }

    /**
     * @page  user
     * @point 修改状态
     */
    public function userForbiddenAction()
    {
        list($flg, $rec) = $this->service->userForbidden($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }


    /**
     * @page  user
     * @point 用户账户变化历史
     */
    public function payHistoryAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';
        switch ($c) {
            case 'export':
                ExportService::addTask($this->uid, 'pay_history_list.csv', [PayHistoryExport::class, 'export'], $this->params, '用户账户变化历史');
                return $this->outputSuccess();
            default:
                list($data, $other) = $this->service->payHistory($this->params);
                return $this->outputSuccess($data, $other);
        }

    }

    /**
     * @page  user
     * @point 账户变化历史
     */
    public function payUserHistoryAction()
    {
        $params = $this->params;
        $c = $params['c'] ?? '';

        switch ($c) {
            case 'export':
                ExportService::addTask($this->uid, 'pay_user_history_list.csv', [PayUserHistoryExport::class, 'export'], $this->params, '账户变化历史');
                return $this->outputSuccess();
            default:
                $data = $this->service->payUserHistory($params);
                return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
        }
    }


    /**
     * @page  user
     * @point 罚款
     */
    public function userPunishAction()
    {
        list($flg, $rec) = $this->service->userPunish($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 指定账户罚款
     */
    public function accountPunishAction()
    {
        list($flg, $rec) = $this->service->accountPunish($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 更改性别
     */
    public function userSexAction()
    {
        list($flg, $rec) = $this->service->userSex($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 更改昵称为无效
     */
    public function userNameModifyAction()
    {
        list($flg, $rec) = $this->service->userNameModify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 设置签名为无效
     */
    public function userSignModifyAction()
    {
        list($flg, $rec) = $this->service->userSignModify($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 改为先审后发
     */
    public function userValidReviewModAction()
    {
        list($flg, $rec) = $this->service->userValidReviewMod($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 禁止修改
     */
    public function userValidForbiddenAction()
    {
        list($flg, $rec) = $this->service->userValidForbidden($this->params);
        return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
    }

    /**
     * @page  user
     * @point 聊天历史
     */
    public function getChatLogAction()
    {
        $data = $this->service->getOrderChatLog($this->params);
        return $this->outputSuccess($data['data'] ?? [], ['total' => $data['total'] ?? 0]);
    }

    /**
     * @page  user
     * @point 可修改50级以上财富等级
     */
    public function fiftyAction()
    {
    }

}