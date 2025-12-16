<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Models\Xs\XsUserForbiddenLog;
use Imee\Models\Xsst\XsstUserForbiddenCheck;

class ConfigProcess
{
    public function handle()
    {
        $format = [];

        foreach (XsstUserForbiddenCheck::$status_arr as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['status'][] = $tmp;
        }

        foreach (XsUserForbiddenLog::$op_arr as $key => $val) {
            $tmp['label'] = $val;
            $tmp['value'] = $key;
            $format['op'][] = $tmp;
        }

        foreach (XsUserForbiddenLog::$source_arr as $key => $val) {
            $tmp['label'] = $val;
            if ($key == '') {
                $key = 'unknown';
            }
            $tmp['value'] = $key;
            $format['source'][] = $tmp;
        }



        // foreach (XsUserProfile::$deleted_arr as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['handle_deleted'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$displayDuration as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['duration'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$displayMacneed as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['macneed'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$displayMacneedphone as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['macneedphone'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$displayReason as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $v;
        //     $format['reason'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$displayGodReason as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $v;
        //     $format['god_reason'][] = $tmp;
        // }

        // foreach (XsUserForbiddenLog::$unforbiddenReason as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['unforbidden_reason'][] = $tmp;
        // }

        // foreach (BmsUnforbiddenLog::$state as $k => $v) {
        //     $tmp['label'] = $v;
        //     $tmp['value'] = $k;
        //     $format['state'][] = $tmp;
        // }

        return $format;
    }
}
