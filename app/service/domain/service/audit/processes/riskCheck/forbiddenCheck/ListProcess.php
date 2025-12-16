<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Models\Lemon\UserActivenessLevel;
use Imee\Models\Xsst\XsstUserForbiddenCheck;

use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xs\XsUserProfile;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

/**
 * 封禁核查列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $leftClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsUserForbiddenLog::class;
        $this->query = XsUserForbiddenLog::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];
        $where['condition'][] = 'check = :check:';
        $where['bind']['check'] = 0;
        

        if ($this->context->isCheckUserForbidden) {
            $where['condition'][] = 'deleted > :deleted:';
            $where['bind']['deleted'] = 1;
        } else {
            $where['condition'][] = 'deleted <= :deleted:';
            $where['bind']['deleted'] = 1;
        }
        

        if ($this->context->status) {
            $status = $this->context->status;
            $t = strtotime('-3 day');

            
            if ($status == 1) {
                $check = XsstUserForbiddenCheck::find(array(
                    'columns' => 'log_id',
                    'conditions' => "dateline >= $t ",
                ))->toArray();
                if (!empty($check)) {
                    $log_ids = array_column($check, 'log_id');

                    $where['condition'][] = 'id not in ({ids:array})';
                    $where['bind']['ids'] = $log_ids;
                }
            } else {
                
                $check = XsstUserForbiddenCheck::find(array(
                    'columns' => 'log_id',
                    'conditions' => "status = $status  and dateline >= $t ",
                ))->toArray();
            
                if (!empty($check)) {
                    $log_ids = array_column($check, 'log_id');
                    $where['condition'][] = 'id in ({ids:array})';
                    $where['bind']['ids'] = $log_ids;
                } else {
                    $where['condition'][] = 'id in ({ids:array})';
                    $where['bind']['ids'] = [-1];
                }
            }
        }

        if ($this->context->start) {
            $startTime = strtotime($this->context->start);
            $startTime = $startTime >= 1600941600 ? $startTime : 1600941600;
            $where['condition'][] = " dateline >= :start_time:";
            $where['bind']['start_time'] = $startTime;
        } else {
            $where['condition'][] = " dateline >= :start_time:";
            $where['bind']['start_time'] = 1600941600;
        }

        if ($this->context->end) {
            $where['condition'][] = " dateline < :end_time:";
            $where['bind']['end_time'] = strtotime($this->context->end) + 86400;
        }

        if ($this->context->uid) {
            $where['condition'][] = " uid = :uid:";
            $where['bind']['uid'] = $this->context->uid;
        }

        if ($this->context->op) {
            if ($this->context->op == 1) {
                $where['condition'][] = " op = :op:";
                $where['bind']['op'] = 0;
            }
            if ($this->context->op == 2) {
                $where['condition'][] = " op > :op:";
                $where['bind']['op'] = 0;
            }
        }

        if ($this->context->source) {
            $source = $this->context->source == 'unknown' ? '' : $this->context->source;
            $where['condition'][] = " source = :source:";
            $where['bind']['source'] = $source;
        }

        $this->where = $where;
    }

    protected function formatList($items)
    {
        $format = [];
        if (empty($items)) {
            return $format;
        }
        $opIds = [];
        $uids = [];
        $ids = [];
        foreach ($items as $items) {
            $ids[] = $items->id;
            $opIds[] = $items->op;
            $uids[] = $items->uid;
            $format[] = $items->toArray();
        }

        if (empty($format)) {
            return $format;
        }

        $xsstUserForbiddenCheckArr = XsstUserForbiddenCheck::find([
            'conditions' => 'log_id in ({ids:array})',
            'bind' => ['ids' => $ids],
        ])->toArray();
        $userForbiddenCheckInfo = array_column($xsstUserForbiddenCheckArr, null, 'log_id');

        $staffMap = $this->getStaffBaseInfos(array_values(array_unique($opIds)));

        $userMap = $this->getUserInfoModel(array_values(array_unique($uids)))
            ->language()
            ->vip()
            ->title()
            ->handle();

        $all_source = XsUserForbiddenLog::$source_arr;
        // 活跃等级
        $activeLevel = UserActivenessLevel::handleList(array(
            'uid_array' => array_values($uids),
            'columns' => ['uid', 'point'],
        ));
        $activeLevel = array_column($activeLevel, 'point', 'uid');
        $newActiveLevel = [];
        foreach ($uids as $uid) {
            $newActiveLevel[$uid] = isset($activeLevel[$uid]) ? UserActivenessLevel::getLevel($activeLevel[$uid]) : 0;
        }
        foreach ($format as &$val) {
            $val['active_level'] = $newActiveLevel[$val['uid']] ?? 0;
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            if ($val['op'] == 0) {
                if ($val['deleted'] > 1) {
                    $val['reason'] = '系统封禁';
                }
                $val['op_name'] = '系统';
            } else {
                $op_id = $val['op'];
                $val['op_name'] = isset($staffMap[$op_id]) ? $staffMap[$op_id]['user_name'] : ' - ';
            }
            if ($val['duration'] > 1) {
                if (intval($val['duration']) >= 86400) {
                    $val['duration'] = intval($val['duration'] / 86400) . '天';
                } elseif (intval($val['duration']) < 86400 && intval($val['duration']) > 0) {
                    $val['duration'] = intval($val['duration'] / 3600) . '小时';
                }
            } else {
                if ($val['deleted'] > 1) {
                    $val['duration'] = '永久';
                } else {
                    $val['duration'] = '';
                }
            }
            $val['role'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['role'] : 0;
            $val['name'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['name'] : '-';
            $val['language'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['language_name'] : '-';

            $val['user_status'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['deleted'] : -1;

            $val['display_user_status'] = isset(XsUserProfile::$deleted_arr[$val['user_status']])
                ? XsUserProfile::$deleted_arr[$val['user_status']] : '机器';
            $val['vip'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['vip'] : 0;
            $val['title'] = isset($userMap[$val['uid']]) ? $userMap[$val['uid']]['title_name'] : '无';

           
            $val['status'] = isset($userForbiddenCheckInfo[$val['id']]) ?
                $userForbiddenCheckInfo[$val['id']]['status'] : 1;
            $val['display_status'] = XsstUserForbiddenCheck::$status_arr[$val['status']];
            $val['handle_reason'] = isset($userForbiddenCheckInfo[$val['id']]) ?
                $userForbiddenCheckInfo[$val['id']]['reason'] : '-';
            
            $val['device_name'] = $val['device'] ? '封禁设备' : '不封禁设备';

            $val['source_name'] = isset($all_source[$val['source']]) ? $all_source[$val['source']] : '';

            $val['forbidden_type'] = XsUserForbiddenLog::$deleted_arr[$val['deleted']] ?? '';
        }

        return $format;
    }
}
