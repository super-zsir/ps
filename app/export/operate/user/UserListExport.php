<?php

namespace Imee\Export\Operate\User;

use Dcat\EasyExcel\Excel;
use Imee\Service\Operate\User\UserListService;

class UserListExport
{
    public $header = [];
    public $lang = 'en';


    public static function export($filePathName, $filterParams): bool
    {
        $lang = $filterParams['lang'] ?? 'en';
        $headings = [
            'uid'                  => 'UID',
            'name'                 => __T('昵称', [], $lang),
            'vip'                  => __T('vip（废弃）', [], $lang),
            'nvip'                 => __T('VIP', [], $lang),
            'title_new'            => __T('爵位', [], $lang),
            'sex'                  => __T('性别', [], $lang),
            'icon'                 => __T('头像', [], $lang),
            'video'                => __T('视频', [], $lang),
            'role'                 => __T('角色', [], $lang),
            'app_name'             => __T('app', [], $lang),
            'service_score'        => __T('设定', [], $lang),
            'god_num'              => __T('接单', [], $lang),
            'bname'                => __T('公会', [], $lang),
            'uarea'                => __T('地区', [], $lang),
            'city'                 => __T('城市', [], $lang),
            'birthday'             => __T('生日', [], $lang),
            'platform'             => __T('系统', [], $lang),
            'channel'              => __T('渠道', [], $lang),
            'version'              => __T('版本', [], $lang),
            'dateline'             => __T('注册日期', [], $lang),
            'online_status'        => __T('在线', [], $lang),
            'online_dateline'      => __T('在线时间', [], $lang),
            'deleted'              => __T('状态', [], $lang),
            'is_service_forbidden' => __T('接单状态', [], $lang),
            'money'                => __T('钻石(币)', [], $lang),
            'money_b'              => __T('虚拟币(币)', [], $lang),
            'money_agent'          => __T('转账钻石(币)', [], $lang),
            'money_cash'           => __T('魅力值(币)', [], $lang),
            'money_cash_live'           => __T('直播魅力值(币)', [], $lang),
//            'gold_coin'            => __T('金豆数', [], $lang),
            'gold'                 => __T('金币', [], $lang),
//            'chips'                => __T('筹码', [], $lang),
            'money_debts'          => __T('欠款额', [], $lang),
            'danger_receive'       => __T('风险币(币)', [], $lang),
            'language'             => __T('语言', [], $lang),
            'sign'                 => __T('签名', [], $lang),
            'big_area'             => __T('运营大区', [], $lang),
            'price_level'          => __T('财富等级', [], $lang),
        ];

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $headings, $lang) {
                // 每次获取2000条数据导入
                $pageSize = 2000;

                // 只查询前50页数据
                if ($page > 50) {
                    return [];
                }

                $service = new UserListService();

                $data = $service->getListAndTotal(['page' => $page, 'limit' => $pageSize] + $filterParams);

                if (empty($data['data'])) {
                    return [];
                }

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return UserListExport::formatList($data['data'], $headings, $lang);
            })
            ->headings($headings)
            ->store($filePathName);
    }


    public static function formatList($list, $headings, $lang = 'en'): array
    {
        $newList = [];

        $roleMap = [__T('待注册', [], $lang), __T('用户', [], $lang), __T('关闭认证', [], $lang), __T('技能认证', [], $lang)];
        $serviceScore = [' - ', __T('差', [], $lang), __T('合格', [], $lang), __T('好', [], $lang), __T('优秀', [], $lang)];
        $onlineArray = [__T('在线', [], $lang), __T('离线', [], $lang), __T('退出', [], $lang)];
        $deletedArray = [__T('正常', [], $lang), __T('搜不到', [], $lang), __T('禁聊', [], $lang), __T('封禁', [], $lang)];
        foreach ($list as $rec) {
            $rec['version'] = $rec['version'] . ' ... ' . $rec['jversion'];
            if ($rec['platform'] == 1) {
                $rec['platform'] = 'android';
            } elseif ($rec['platform'] == 2) {
                $rec['platform'] = 'ios';
            } else {
                $rec['platform'] = '-';
            }
            if ($rec['sex'] == 1) {
                $rec['sex'] = __T('男', [], $lang);
            } elseif ($rec['sex'] == 2) {
                $rec['sex'] = __T('女', [], $lang);
            } else {
                $rec['sex'] = '-';
            }
            $rec['role'] = $roleMap[$rec['role']] ?? '-';
            $rec['service_score'] = $serviceScore[$rec['service_score']];
            $rec['online_status'] = $onlineArray[$rec['online_status']] ? $onlineArray[$rec['online_status']] : '-';
            if ($rec['deleted'] < 0) {
                $rec['deleted'] = __T('机器', [], $lang);
            } else {
                $rec['deleted'] = $deletedArray[$rec['deleted']] ?? '-';
            }
            
            $tmp = [];
            foreach ($headings as $k => $v) {
                $tmp[$k] = isset($rec[$k]) ? $rec[$k] : '-';
            }

            $newList[] = $tmp;
        }

        return $newList;
    }
}