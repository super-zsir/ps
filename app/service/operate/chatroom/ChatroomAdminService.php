<?php

namespace Imee\Service\Operate\Chatroom;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsChatroomAdminRecord;
use Imee\Models\Xs\XsChatroomRole;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstChatroomRoleRecord;
use Imee\Service\Helper;

/**
 * 聊天室管理员
 */
class ChatroomAdminService
{
    /**
     * @var XsChatroomRole $model
     */
    private $model = XsChatroomRole::class;

    /**
     * @var XsChatroom $chatroomModel
     */
    private $chatroomModel = XsChatroom::class;

    /**
     * @var XsUserProfile $userModel
     */
    private $userModel = XsUserProfile::class;

    /**
     * @var XsstChatroomRoleRecord $recordModel
     */
    private $recordModel = XsstChatroomRoleRecord::class;

    /**
     * @var XsChatroomAdminRecord $recordNewModel
     */
    private $recordNewModel = XsChatroomAdminRecord::class;

    /**
     * @var CmsUser $adminModel
     */
    private $adminModel = CmsUser::class;

    public function getListAndTotal(array $params): array
    {
        $conditions = $this->getConditions($params);
        $list = $this->model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list)) {
            return $list;
        }

        $userList = $this->userModel::getUserProfileBatch(Helper::arrayFilter($list['data'], 'uid'));
        $roomList = $this->chatroomModel::getInfoBatch(Helper::arrayFilter($list['data'], 'rid'), ['rid', 'name']);

        foreach ($list['data'] as &$item) {
            $user = $userList[$item['uid']] ?? [];
            $room = $roomList[$item['rid']] ?? [];
            $item['uname'] = $user['name'] ?? '-';
            $item['rname'] = $room['name'] ?? '-';
            $role = '';
            if ($item['role'] == $this->model::ROLE_CREATOR) {
                $role = '创建者';
            } else if ($item['role'] == $this->model::ROLE_ADMIN) {
                $role = $item['weight'] ? '高级管理员' : '普通管理员';
            }
            $role && $item['role'] = $role;
        }

        return $list;
    }

    /**
     * 获取筛选条件
     * @param array $params
     * @return array
     */
    private function getConditions(array $params): array
    {
        $conditions = [];

        $uid = intval($params['uid'] ?? 0);
        $rid = intval($params['rid'] ?? 0);
        $role = trim($params['role'] ?? '');

        $uid && $conditions[] = ['uid', '=', $uid];
        $rid && $conditions[] = ['rid', '=', $rid];

        if ($role) {
            if (in_array($role, [$this->model::ROLE_CREATOR, $this->model::ROLE_ADMIN])) {
                $conditions[] = ['role', '=', $role];
            } else if (in_array($role, [$this->model::ROLE_ADMIN_ONE, $this->model::ROLE_ADMIN_TWO])) {
                $conditions[] = ['role', '=', $this->model::ROLE_ADMIN];
                $conditions[] = ['weight', '=', $role == $this->model::ROLE_ADMIN_TWO ? $this->model::WEIGHT_HIGH : $this->model::WEIGHT_ORDINARY];
            }
        }

        return $conditions;
    }

    /**
     * 创建房间管理员
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function create(array $params): array
    {
        list($extra, $data) = $this->validate($params);

        list($res, $id) = $this->model::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '管理员添加失败，' . $id);
        }

        $this->addRecord($data, $extra['ac'], $data['weight']);
        $this->sendMessage($data['uid'], $this->getMessage($extra['role'], $extra['name'], $data['weight']));

        return ['id' => $id, 'after_json' => $data];
    }

    /**
     * 修改/取消管理员
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function save(array $params): array
    {
        // 验证一下数据
        list($extra, $data) = $this->validate($params, false);

        // 保持角色不需要走下面流程，直接返回
        if (empty($extra['role'])) {
            return [];
        }

        if ($extra['role'] == $this->model::ROLE_CANCEL) {
            // 取消管理员
            $this->cancel($params['id']);
        } else {
            // 修改管理员
            $this->modify($params['id'], $data['weight']);
        }

        // 取消或者修改管理员角色，记录日志并发送消息
        if ($extra['role'] == $this->model::ROLE_CANCEL || $extra['weight'] != $data['weight']) {
            $this->addRecord($data, $extra['ac'], $data['weight']);
            $this->sendMessage($data['uid'], $this->getMessage($extra['role'], $extra['name'], $data['weight']));
        }

        return ['id' => $params['id'], 'after_json' => $data];
    }

    /**
     * 取消管理员
     * @param int $id
     * @param string $rname
     * @param array $data
     * @return void
     * @throws ApiException
     */
    private function cancel(int $id): void
    {
        $res = $this->model::deleteById($id);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '取消管理员失败，' . $id);
        }
    }

    /**
     * 修改管理员
     * @param int $id
     * @param int $weight
     * @return void
     */
    private function modify(int $id, int $weight): void
    {
        list($res, $msg) = $this->model::edit($id, ['weight' => $weight]);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '管理员修改失败，' . $msg);
        }
    }

    /**
     * 验证数据
     * @param array $params
     * @param bool $isCreate
     * @return array
     * @throws ApiException
     */
    private function validate(array $params, bool $isCreate = true): array
    {
        $id = intval($params['id'] ?? 0);
        $uid = intval($params['uid'] ?? 0);
        $rid = intval($params['rid'] ?? 0);
        $role = intval($params['role'] ?? 0);

        $admin = $this->model::findOne($id);
        if ($id && !$admin) {
            throw new ApiException(ApiException::MSG_ERROR, '数据不存在');
        }

        $room = $this->chatroomModel::findOne($rid);
        if (!$room) {
            throw new ApiException(ApiException::MSG_ERROR, '房间不存在');
        }

        if (!$this->userModel::findOne($uid)) {
            throw new ApiException(ApiException::MSG_ERROR, '用户不存在');
        }

        if ($isCreate && $this->model::getInfoByRidAndUid($rid, $uid)) {
            throw new ApiException(ApiException::MSG_ERROR, '该用户已经是管理员');
        }

        if ($role == $this->model::ROLE_HIGH && $this->model::getHighAdminCountByRid($rid) >= $this->model::HIGH_ADMIN_NUM) {
            throw new ApiException(ApiException::MSG_ERROR, sprintf('房间高级管理员数量已达到%s个，无法再添加', $this->model::HIGH_ADMIN_NUM));
        }

        $data = [
            'uid'      => $uid,
            'rid'      => $rid,
            'role'     => $this->model::ROLE_ADMIN,
            'creator'  => $room['uid'],
            'set_time' => time(),
            'weight'   => $role == $this->model::ROLE_HIGH ? $this->model::WEIGHT_HIGH : $this->model::WEIGHT_ORDINARY,
        ];

        $extra = [
            'weight' => $admin['weight'] ?? 0,
            'name'   => $room['name'] ?? '',
            'role'   => $role,
            'ac'     => $role == $this->model::ROLE_CANCEL ? $this->recordModel::ACTION_CANCEL : $this->recordModel::ACTION_ADD
        ];

        return [$extra, $data];
    }

    /**
     * 记录日志
     * @param array $data
     * @param string $ac
     * @param int $weight
     * @return void
     */
    private function addRecord(array $data, string $ac = 'roleAdd', int $weight = 0): void
    {
        $recordData = [
            'rid'      => $data['rid'],
            'uid'      => Helper::getSystemUid(),
            'to'       => $data['uid'],
            'ac'       => $ac,
            'acdata'   => $weight,
            'dateline' => time()
        ];

        $this->recordModel::add($recordData);
    }

    /**
     * 获取管理设置消息文案
     * @param int $role
     * @param string $name
     * @param string $weight
     * @return string
     */
    private function getMessage(int $role, string $name, string $weight): string
    {
        if ($role == $this->model::ROLE_CANCEL) {
            return sprintf('您在%s房间的管理权限已被取消', $name);
        }

        // 发送消息
        $weightMsgMap = $this->model::$weightMsgMap;
        return str_replace(
            ['{name}', '{purview}'],
            [$name, $this->model::$weightMsgMap[$weight]],
            "您已经成为{name}房间的{purview}管理员"
        );
    }

    /**
     * 发送消息
     * @param int $uid
     * @param string $message
     * @return void
     */
    private function sendMessage(int $uid, string $message): void
    {
        NsqClient::publish(NsqConstant::TOPIC_XS_ADMIN, array(
            'cmd'  => 'system.message',
            'data' => array(
                'from'    => 0,
                'uid'     => $uid,
                'message' => $message
            )
        ));
    }

    /**
     * 管理员操作记录
     * @param array $params
     * @return array
     */
    public function getHistoryListAndTotal(array $params): array
    {
        // 根据查询isadmin判断对应查询model是哪一个
        [$model, $conditions, $isadmin] = $this->getHistoryConditions($params);

        $list = $model::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $uidArr = array_merge(Helper::arrayFilter($list['data'], 'to'), Helper::arrayFilter($list['data'], 'uid'));

        $userList = $this->userModel::getUserProfileBatch($uidArr);
        $adminList = $this->adminModel::getUserNameList($uidArr);
        $roomList = $this->chatroomModel::getInfoBatch(Helper::arrayFilter($list['data'], 'rid'), ['rid', 'name']);

        foreach ($list['data'] as &$item) {
            // 根据查询isadmin判断uname取后台用户还是C端用户
            if ($isadmin == 1) {
                $item['uname'] = $adminList[$item['uid']] ?? '-';
            } else {
                $item['uname'] = ($userList[$item['uid']] ?? [])['name'] ?? '-';
            }
            $item['toname'] = ($userList[$item['to']] ?? [])['name'] ?? '-';
            $item['rname'] = ($roomList[$item['rid']] ?? [])['name'] ?? '-';
            $item['dateline'] = Helper::now($item['dateline']);
            $item['isadmin'] = $isadmin == 1 ? '官方后台' :'APP内';
            $ac = '';
            if ($item['ac'] == $this->recordModel::ACTION_CANCEL) {
                $ac = '取消管理';
            } else if ($item['ac'] == $this->recordModel::ACTION_ADD) {
                $ac = $item['acdata'] == $this->model::WEIGHT_HIGH ? '设置高级管理员' : '设置普通管理员';
            }
            $ac && $item['ac'] = $ac;
        }

        return $list;
    }

    /**
     * 获取管理员操作查询条件
     * @param array $params
     * @return array
     */
    private function getHistoryConditions(array $params): array
    {
        $conditions = [];

        $uid = intval($params['uid'] ?? 0);
        $to = intval($params['to'] ?? 0);
        $creater = intval($params['creater'] ?? 0);
        $ac = trim($params['ac'] ?? '');
        $isadmin = intval($params['isadmin'] ?? 0);

        $uid && $conditions[] = ['uid', '=', $uid];
        $to && $conditions[] = ['to', '=', $to];
        $creater && $conditions[] = ['rid', '=', $creater];

        $isadmin != 1 && $conditions[] = ['ac', 'IN', $this->recordModel::ACTION_ADD, $this->recordModel::ACTION_CANCEL];
        if ($ac == $this->recordModel::ACTION_CANCEL) {
            $conditions[] = ['ac', '=', $ac];
        } else if (str_contains($ac, 'roleAdd')) {
            $conditions[] = ['ac', '=', $this->recordModel::ACTION_ADD];
            $conditions[] = ['acdata', '=', $ac == 'roleAdd_2' ? $this->model::WEIGHT_HIGH : $this->model::WEIGHT_ORDINARY];
        }
        return [$this->getHistoryModel($isadmin), $conditions, $isadmin];
    }

    /**
     * 获取操作日志model
     * @param $isadmin
     */
    private function getHistoryModel(int $isadmin): string
    {
        return $isadmin == 1 ? $this->recordModel : $this->recordNewModel;
    }


}