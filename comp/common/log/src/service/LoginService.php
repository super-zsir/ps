<?php

namespace Imee\Comp\Common\Log\Service;

use Dcat\EasyExcel\Excel;
use Imee\Comp\Common\Log\Models\Xsst\BmsLoginLog;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Service\Helper;
use Imee\Service\Lesscode\ExportService;

/**
 * 登录日志服务
 */
class LoginService
{
    public static function getList(array $params): array
    {
        $conditions = [];

        if (isset($params['admin_name']) && !empty($params['admin_name'])) {
            $conditions[] = ['admin_name', 'like', "%{$params['admin_name']}%"];
        }
        if (isset($params['dateline_sdate']) && !empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (isset($params['dateline_edate']) && !empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<=', strtotime($params['dateline_edate'])];
        }
        if (isset($params['ip']) && !empty($params['ip'])) {
            $conditions[] = ['ip', '=', ip2long($params['ip'])];
        }

        $list = BmsLoginLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $adminList = CmsUser::getAdminUserBatch(Helper::arrayFilter($list['data'], 'admin_id'), ['user_id', 'user_name', 'user_email']);

        foreach ($list['data'] as &$item) {
            $browser = self::parseUserAgent($item['browser']);
            $item['dateline'] = Helper::now($item['dateline']);
            $item['ip'] = long2ip($item['ip']);
//            $item['ip'] = $item['ip'] . ' 【' . get_address_by_ipv4($item['ip']) . '】';
            $item['terminal'] = $browser['browser'];
            $item['operating_system'] = $browser['os'];
            $item['type'] = BmsLoginLog::$typeMap[$item['type']] ?? '';
            $item['admin_email'] = $adminList[$item['admin_id']]['user_email'] ?? '';
        }
        return $list;
    }

    private static function parseUserAgent($userAgent): array
    {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // 解析浏览器信息
        if (preg_match('/(MSIE|Trident)/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browser = 'Opera';
        }

        // 解析操作系统信息
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $os = 'Macintosh';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Unix/i', $userAgent)) {
            $os = 'Unix';
        }

        return [
            'browser' => $browser,
            'os'      => $os,
        ];
    }

    public static function addLog(array $params, $type = null): array
    {
        if ($type == 'logout') {
            $type = BmsLoginLog::LOGOUT_TYPE;
        } else {
            $type = BmsLoginLog::LOGIN_TYPE;
        }

        $data = [
            'admin_id'   => $params['admin_id'] ?? Helper::getSystemUid(),
            'admin_name' => $params['admin_name'] ?? (Helper::getSystemUserInfo()['user_name'] ?? ''),
            'type'       => $type,
            'ip'         => ip2long(Helper::ip()),
            'browser'    => $_SERVER['HTTP_USER_AGENT'],
        ];

        return BmsLoginLog::add($data);
    }

    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = self::getList(['page' => $page, 'limit' => $pageSize] + $filterParams);
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }

    /**
     * 获取表头
     */
    private static function getHeader($guid = '', $language = 'zh_cn'): array
    {
        static $header;
        if ($header) {
            return $header;
        }

        $header = ExportService::getListFields($guid, $language);
        return $header;
    }
}