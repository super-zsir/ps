<?php

namespace Imee\Service\Domain\Service\Risk;

use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsstForbiddenRiskLog;

class RiskUserService
{
    public static function record($uid, $opId, $opType, $ruleType, $duration, $reason, $language)
    {
        $user = XsUserProfile::findFirstValue($uid);
        if (empty($user)) {
            return false;
        }
        $row['new_user'] = 0;
        if ((time() - $user->dateline) <= 7 * 86400) {
            $row['new_user'] = 1;
        }
        $row['role'] = $user->role;
        $row['op_type'] = $opType;
        $row['op_id'] = $opId;
        $row['rule_type'] = $ruleType;
        $row['duration'] = $duration;
        $row['reason'] = $reason;
        $row['uid'] = $uid;
        $row['language'] = $language;
        XsstForbiddenRiskLog::createLog($row);
    }
}
