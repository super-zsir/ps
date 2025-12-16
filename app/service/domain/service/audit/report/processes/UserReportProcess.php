<?php

namespace Imee\Service\Domain\Service\Audit\Report\Processes;

use Imee\Models\Lemon\UserActivenessLevel;
use Imee\Models\Lemon\UserVip;
use Imee\Models\Xs\XsChatroom;
use Imee\Models\Xs\XsReport;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\XssReport;
use Imee\Models\Xsst\XsstReportExamLog;
use Imee\Service\Domain\Service\Audit\Report\Consts\CommonConst;
use Imee\Service\Domain\Service\Audit\Report\Context\UserReportContext;
use Imee\Service\Domain\Service\Audit\Report\Exception\BaseException;
use Imee\Service\Domain\Service\Audit\Report\Traits\CommonTrait;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

class UserReportProcess
{
    use CommonTrait;
    use UserInfoTrait;
    use CsmsTrait;

    /**
     * 举报列表
     * @param UserReportContext $context
     * @return array
     */
    public function handle(UserReportContext $context): array
    {
        $conditions = array(
            'rid_lg' => $context->ridLg,
            'rid' => $context->rid,
            'type' => $context->type,
            'state' => $context->state ? ($context->state - 1) : '',
            'uid' => $context->uid,
            'to' => $context->to,
            'language' => $context->language,
            'app_id' => $context->appId,
            'big_area' => $context->fromBigArea,
            'dateline_start' => $context->datelineStart ? strtotime($context->datelineStart) : '',
            'dateline_end' => $context->datelineEnd ? strtotime($context->datelineEnd) : '',
            'offset' => $context->offset,
            'limit' => $context->limit,
            'orderBy' => empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
        );
        if ($context->property) {
            $ridNeed = XsChatroom::find(array(
                'property=:property:',
                'bind' => array('property' => $context->property)
            ))->toArray();
            $ridNeed = array_column($ridNeed, 'rid');
            $ridNeed = array_merge(array(-1), $ridNeed);
            $conditions['rid_array'] = $ridNeed;
        }
        if ($context->sxvip > 0) {
            $userVip = UserVip::find(array(
                'level >= :level:',
                'bind' => array('level' => $context->sxvip)
            ))->toArray();
            $conditions['uid_array'] = array_column($userVip, 'uid');
        }
        if ($context->sxvip2 > 0) {
            $userVip = UserVip::find(array(
                'level >= :level:',
                'bind' => array('level' => $context->sxvip2)
            ))->toArray();
            $conditions['to_array'] = array_column($userVip, 'uid');
        }
        $conditions['state'] = $conditions['state'] === -1 ? '' : $conditions['state'];
        $conditions = self::commonFilter($conditions);

        $list = XssReport::handleList($conditions);
        unset($conditions['limit']);
        unset($conditions['offset']);
        $total = XssReport::handleTotal($conditions);
        $list = $this->_formatReportData($list);
        return ['data' => $list, 'total' => $total > count($list) ? count($list) : $total];
    }

    /**
     * @param $data
     * @return mixed
     */
    private function _formatReportData($data)
    {
        if (empty($data)) {
            return $data;
        }
        $columnData = array_column($data, null, 'id');
        $id_array = array_column($data, 'id');
        $data = XsReport::find([
            'conditions' => 'id in ({ids:array})',
            'bind' => [
                'ids' => $id_array
            ]
        ])->toArray();
        if (empty($data)) {
            return $data;
        }
        $uids = array();
        $rids = array();
        foreach ($data as $v) {
            $uids[] = intval($v['uid']);
            $uids[] = intval($v['to']);
            $rids[] = intval($v['rid']);
        }
        $uids = array_values(array_unique($uids));
        // 查找用户vip
        $userVipInfo = UserVip::find(array(
            'columns' => "uid, level",
            "uid in ({ids:array})",
            "bind" => array("ids" => $uids)
        ))->toArray();
        $userVipInfo = array_column($userVipInfo, 'level', 'uid');
        // 查找用户爵位
//        $userNobility = UserNobility::find(array(
//            'columns' => "uid, level",
//            "uid in ({ids:array})",
//            "bind" => array("ids" => $uids)
//        ))->toArray();
//        $userNobility = array_column($userNobility, 'level', 'uid');
        // 用户信息
        $userInfoMap = XsUserProfile::getUserProfileBatch(
            $uids,
            ['uid', 'name', 'app_id', 'role', 'pay_room_money', 'title', 'deleted']
        );
        // 操作信息
        $logList = $this->getLatestLog($id_array);
        // 被举报人违规信息
        $userDanger = $this->getUserDanger($uids);
        // 房间信息
        $chatroomInfoMap = XsChatroom::getInfoBatch($rids, ['rid', 'name', 'types', 'property', 'deleted', 'app_id', 'uid']);
        // 活跃等级
        $activeLevel = UserActivenessLevel::handleList(array(
            'uid_array' => $uids,
            'columns' => ['uid', 'point'],
        ));
        $activeLevel = array_column($activeLevel, 'point', 'uid');
        $newActiveLevel = [];
        foreach ($uids as $uid) {
            $newActiveLevel[$uid] = isset($activeLevel[$uid]) ? UserActivenessLevel::getLevel($activeLevel[$uid]) : 0;
        }
        foreach ($data as &$rec) {
            $rec['dateline'] = $rec['dateline'] > 0 ? date('Y-m-d H:i:s', $rec['dateline']) : '';
            $rec['active_level'] = $newActiveLevel[$rec['uid']] ?? 0;
            $rec['active_level_to'] = $newActiveLevel[$rec['to']] ?? 0;
            $rec['language'] = isset($columnData[$rec['id']]['language']) ? Helper::getLanguageName($columnData[$rec['id']]['language']) : '-';
            $rec["uname"] = $userInfoMap[$rec["uid"]]['name'] ?? '';
            $rec["urole"] = $userInfoMap[$rec["uid"]]['role'] ?? '';
            $rec['uvip'] = $userVipInfo[$rec["uid"]] ?? 0;
//            $rec['utitle'] = $userNobility[$rec["uid"]] ?? 0;
            $rec['u_app_name'] = isset($userInfoMap[$rec["uid"]]['app_id']) ? Helper::getAppName($userInfoMap[$rec["uid"]]['app_id']) : '-';
            $rec["toname"] = $userInfoMap[$rec["to"]]['name'] ?? '';
            $rec["torole"] = $userInfoMap[$rec["to"]]['role'] ?? '';
            $rec["todeleted"] = $userInfoMap[$rec["to"]]['deleted'] ?? '';
            $rec["tovip"] = $userVipInfo[$rec["to"]] ?? 0;
//            $rec["totitle"] = $userNobility[$rec["to"]] ?? 0;
            $rec['to_app_name'] = isset($userInfoMap[$rec["to"]]['app_id']) ? Helper::getAppName($userInfoMap[$rec["to"]]['app_id']) : '-';

            $rec["to_room_name"] = isset($chatroomInfoMap[$rec['rid']]) ? $chatroomInfoMap[$rec['rid']]['name'] : '';
            $rec["to_room_types"] = isset($chatroomInfoMap[$rec['rid']]) ?
                (CommonConst::roomTypesArr[$chatroomInfoMap[$rec['rid']]['types']] ?? '') : '';
            $rec["to_room_property"] = isset($chatroomInfoMap[$rec['rid']]) ?
                (CommonConst::roomPropertyArr[$chatroomInfoMap[$rec['rid']]['property']] ?? '') : '';
            $rec["to_room_deleted"] = isset($chatroomInfoMap[$rec['rid']]) ? $chatroomInfoMap[$rec['rid']]['deleted'] : '';
            $rec["to_room_app_name"] = isset($chatroomInfoMap[$rec['rid']]) ?
                Helper::getAppName($chatroomInfoMap[$rec['rid']]['app_id']) : '';
            $rec['admin_name'] = $logList[$rec['id']]['user_name'] ?? '';
            $rec['spend_time'] = isset($logList[$rec['id']]['dateline']) ? ($logList[$rec['id']]['dateline'] - $rec['dateline']) : '';
            $rec['refuse_num'] = $userDanger[$rec['to']]['refuse'] ?? 0;
            $rec['forbidden_num'] = $userDanger[$rec['to']]['forbidden'] ?? 0;
            if (isset($chatroomInfoMap[$rec['rid']])) {
                // 房主
                $room_uid = $chatroomInfoMap[$rec['rid']]['uid'];
                $rec["room_uid"] = $room_uid;
                $rec["room_uid_deleted"] = isset($userInfoMap[$room_uid]) ? $userInfoMap[$room_uid]['deleted'] : 0;
                $rec["room_uid_role"] = isset($userInfoMap[$room_uid]) ? $userInfoMap[$room_uid]['role'] : 0;
            } else {
                $rec["room_uid"] = 0;
                $rec["room_uid_deleted"] = 0;
                $rec["room_uid_role"] = 0;
            }
            for ($i = 1; $i <= 8; $i++) {
                if (strrchr($rec["p" . $i], ".mov") == ".mov") {
                    $video = self::_getNewHeadUrl($rec["p" . $i]);
                    if ($video) {
                        $rec['content']["video"][] = $video;
                    }
                    unset($rec["p" . $i]);
                } else {
                    $img = self::_getNewHeadUrl($rec["p" . $i]);
                    if ($img) {
                        $rec['content']['images'][] = $img;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 审核并发送通知
     * @param int $v
     * @param int $state
     * @param string $reason
     * @param $logs
     * @return void
     */
    public function check(int $v, UserReportContext $context, &$logs = [])
    {
        $oriData = XsReport::findFirst(array("id=" . $v));
        if (!$oriData) BaseException::throwException(BaseException::DATA_ERROR);
        if ($oriData->state == $context->state)  BaseException::throwException(BaseException::STATE_UNCHANGE);
        $oriData->state = $context->state;
        $oriData->save();

        $logs[] = array(
            'report_id' => $oriData->id,
            'admin' => $context->admin,
            'state' => $context->state
        );
        $msgTime = date("Y-m-d H:i", $oriData->dateline);
        if ($oriData->rid > 0) {
            $msgToData = XsChatroom::findFirst($oriData->rid);
            if (!$msgToData) BaseException::throwException(BaseException::DATE_ERROR_MSG_SEND_FAIL);
            $msgToUid = $oriData->rid;
            $msgToName = $msgToData->name;
        } else {
            $msgToData = XsUserProfile::findFirstValue($oriData->to);
            if (!$msgToData) BaseException::throwException(BaseException::DATE_ERROR_MSG_SEND_FAIL);
            $msgToUid = $oriData->to;
            $msgToName = $msgToData->name;
        }

        $noticeMsg = '';
        if ($context->state == 1) {
            $noticeMsg = '您好，工作人员正在处理您【%s】对【%s】【%s】的举报';
            $noticeMsg = sprintf($noticeMsg, $msgTime, $msgToUid, $msgToName);
        } else if ($context->state == 2) {
            $noticeMsg = '您好，您【%s】对【%s】【%s】的举报已处理';
            $noticeMsg = sprintf($noticeMsg, $msgTime, $msgToUid, $msgToName);
        } else if ($context->state == 3) {
            $noticeMsg = '您好，由于【%s】，您【%s】对【%s】【%s】的举报已被驳回';
            $noticeMsg = sprintf($noticeMsg, $context->reason, $msgTime, $msgToUid, $msgToName);
        }
        if ($noticeMsg) {
            self::sendSystemMessage($oriData->uid, $noticeMsg);
        }
    }

    /**
     * @param array $condition
     * @return false
     */
    public function addLogBatch(array $condition)
    {
        $insert_array = [];
        foreach ($condition as $item) {
            $insert_array[] = array(
                'report_id' => $item['report_id'],
                'admin' => $item['admin'],
                'state' => $item['state'],
            );
        }
        return XsstReportExamLog::addBatch($insert_array)[0];
    }

    /**
     * 操作日志
     * @param int $id
     * @return array
     */
    public function getLog(UserReportContext $context)
    {
        $res = XsstReportExamLog::find(array(
            'report_id=:rid:',
            'order' => 'id desc',
            'bind' => array('rid' => $context->id)
        ))->toArray();
        $admin = array_column($res, 'admin');
        $adminInfo = $this->getStaffBaseInfos($admin);
        foreach ($res as &$val) {
            $rec = $adminInfo[$val['admin']]['user_name'] ?? '';
            $val['admin'] = $rec;
            $val['dateline'] = $val['create_time'];
        }
        return ['data' => $res, 'total' => count($res)];
    }

    /**
     * @param array $id_array
     * @return array
     */
    public function getLatestLog(array $id_array)
    {
        // 查看个人日志
        $logList = XsstReportExamLog::handleList(array(
            'report_id_array' => $id_array,
            'orderBy' => 'create_time desc',
            'columns' => ['report_id', 'admin', 'state', 'create_time']
        ));
        $newList = [];
        if ($logList) {
            foreach ($logList as $item) {
                if (!isset($newList[$item['report_id']])) {
                    $item['dateline'] = strtotime($item['create_time']);
                    $newList[$item['report_id']] = $item;
                }
            }
        }
        $logList = $newList;
        // 获取客服名称
        $admin = array_column($logList, 'admin');
        $adminInfo = $this->getStaffBaseInfos($admin);
        foreach ($logList as &$logItem) {
            $logItem['user_name'] = $adminInfo[$logItem['admin']]['user_name'] ?? '';
        }
        return $logList;
    }

    public function sevenDayLog()
    {
        XsstReportExamLog::handleList(array(

        ));
    }
}
