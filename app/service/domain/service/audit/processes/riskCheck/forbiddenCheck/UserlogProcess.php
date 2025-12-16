<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Models\Xs\XsUserForbiddenLog;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\UserlogContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\StatusService;

/**
 * 封禁核查列表
 */
class UserlogProcess
{
    use UserInfoTrait;

    protected $context;

    public function __construct(UserlogContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $return = [
            'data' => [],
            'total' => 0,
        ];
        $format = XsUserForbiddenLog::find([
            'conditions' => 'uid = :uid:',
            'bind' => [
                'uid' => $this->context->uid,
            ],
            'order' => 'id desc',
        ])->toArray();
        if (empty($format)) {
            return $return;
        }
        $return['data'] = $this->formatList($format);
        $return['total'] = count($format);
        return $return;
    }

    protected function formatList($format)
    {
        if (!$format) {
            return $format;
        }
        $opUids = [];
        foreach ($format as $v) {
            if (!$v['op']) {
                continue;
            }
            $opUids[] = $v['op'];
        }
        $staffInfoMap = [];
        if (!empty($opUids)) {
            $staffInfoMap = $this->getStaffBaseInfos($opUids);
        }
        foreach ($format as &$val) {
            $val['dateline'] = $val['dateline'] > 0 ? date('Y-m-d H:i:s', $val['dateline']) : '';
            if ($val['op'] == 0) {
                if ($val['deleted'] > 1) {
                    $val['reason'] = '系统封禁';
                }
                $val['op_name'] = '系统';
            } else {
                $val['op_name'] = isset($staffInfoMap[$val['op']]) ?
                    $staffInfoMap[$val['op']]['user_name'] : ' - ';
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
            
            $val['source_name'] = isset(XsUserForbiddenLog::$source_arr[$val['source']]) ?
                XsUserForbiddenLog::$source_arr[$val['source']] : '';

            $val['deleted'] = StatusService::getInstance()->getUserDeletedMap($val['deleted']);
        }
        return $format;
    }
}
