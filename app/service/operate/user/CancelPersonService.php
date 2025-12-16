<?php

namespace Imee\Service\Operate\User;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Kefu\Models\Xs\XsUserCancel;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsUserMoney;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstCancelpersonRecord;
use Imee\Service\Helper;
class CancelPersonService
{
    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $uid = intval(array_get($params, 'uid', 0));
        $status = intval(array_get($params, 'status', 0));
        $start = trim(array_get($params, 'time_sdate', ''));
        $end = trim(array_get($params, 'time_edate', ''));
        $area = intval(array_get($params, 'area', 0));

        $where = [];
        $uid && $where[] = sprintf('a.uid = %d', $uid);
        $status && $where[] = sprintf('a.status = %d', $status);
        $start && $where[] = sprintf('a.dateline > %d', strtotime($start));
        $end && $where[] = sprintf('a.dateline < %d', strtotime($end . ' 23:59:59'));
        $area && $where[] = sprintf('b.bigarea_id = %d', $area);

        $data = XsUserCancel::getListAndTotalBySql($where, $page, $limit);
        $uids = array_column($data['data'], 'uid');
        $priceLevel = UserPriceLevelService::getInstance()->getList($uids);

        foreach ($data['data'] as &$rec) {
            $_uid = intval(array_get($rec, 'uid', 0));
            $dateline = array_get($rec, 'dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';

            $values = XsUserMoney::findFirst(
                array(
                    "uid = :uid:",
                    "bind" => array("uid" => $_uid)
                )
            );
            $profile = XsUserProfile::findFirstValue($_uid);

            $rec['name'] = $profile->name;
            $rec['bigarea'] = Helper::getUserBigAreaNew($_uid);
            $rec['app_name'] = Helper::getAppName($profile->app_id);
            $rec['icon'] = PARTYING_OSS . $profile->icon;
            $rec['vip'] = $priceLevel[$_uid] ?? 0;
            $rec['title'] = $this->getTitleShow($profile->title);
            $rec['deleted'] = $this->getDeletedShow($profile->deleted);
            $rec['role'] = $profile->role;
            $rec['money'] = intval($values->money) / 100;
            $rec['money_cash'] = intval($values->money_cash) / 100;

        }

        return $data;
    }

    public function getLogListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));

        if (empty($id)) {
            return ['data' => [], 'total' => 0];
        }

        $query = [];
        $id && $query[] = ['uid', '=', $id];

        $data = XsstCancelpersonRecord::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $rec['dateline'] = $dateline ? date('Y-m-d H:i:s', $dateline) : '';
            $rec['admin'] = Helper::getAdminName($rec['admin']);
        }
        return $data;
    }

    public function modify($params): array
    {
        $adminId = intval($params['admin_id'] ?? 0);

        $id = intval($params['id'] ?? 0);
        $status = intval($params['status'] ?? 0);
        $reason = trim($params['reason'] ?? '');

        if (!in_array($status, [2, 3])) {
            return [false, '错误的流程'];
        }
        if ($status == 3 && empty($reason)) {
            return [false, '请填写认证理由'];
        }


        $model = XsUserCancel::findOne($id);
        if (empty($model)) {
            return [false, '错误的请求'];
        }

        $update = ['status' => $status, 'verify_dateline' => time()];
        $status == 3 && $update['reason'] = $reason;

        list($flg, $rec) = XsUserCancel::edit($id, $update);
        if ($flg) {
            // 操作记录
            XsstCancelpersonRecord::add(array(
                'uid'      => $id,
                'admin'    => $adminId,
                'status'   => $status,
                'reason'   => $reason,
                'dateline' => time()
            ));
            if ($status == 2) {
                $message = '您的账号注销申请已通过审核，请在3天内在我->账号设置->账号与安全->账号注销里确认是否注销，3天内未确认则需重新提交注销申请';
                $message = Helper::translate($model['uid'], $message);
            } else {
                $message = '由于%s，您的账号注销申请未通过审核';
                $message = Helper::translate($model['uid'], $message);
                $message = sprintf($message, $reason);
            }
            // 发送消息
            NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
                'cmd'  => 'system.message',
                'data' => array(
                    'message' => $message,
                    'uid'     => $model['uid'],
                )
            ));
        }
        return [$flg, $rec];
    }


    public function getTitleShow($title): string
    {
        if ($title < 1) return '-';
        else if ($title == 1) return '子爵';
        else if ($title == 2) return '伯爵';
        else if ($title == 3) return '公爵';
        else if ($title == 4) return '国王';
        else return '皇帝';
    }

    public function getDeletedShow($val): string
    {
        if ($val == 0) {
            return '正常';
        } else if ($val == 1) {
            return '搜不到';
        } else if ($val == 2) {
            return '禁聊';
        } else if ($val == 3) {
            return '封登录';
        } else {
            return $val;
        }
    }
}