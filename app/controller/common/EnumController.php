<?php

namespace Imee\Controller\Common;

use Imee\Controller\BaseController;
use Imee\Service\Domain\Service\Ka\StatusService as KaStatusService;
use Imee\Service\StatusService;

class EnumController extends BaseController
{
    const PARAMS_FORMAT = [null, 'label,value'];

    private $classMap = [
        'area'                    => ['class' => StatusService::class, 'method' => 'getAreaMap', 'params' => self::PARAMS_FORMAT], // 大区
        'language'                => ['class' => StatusService::class, 'method' => 'getUserLanguageMap', 'params' => self::PARAMS_FORMAT], // 语言
        'title_new'               => ['class' => StatusService::class, 'method' => 'getTitleNewMap', 'params' => self::PARAMS_FORMAT], // 爵位
        'user_deleted'            => ['class' => StatusService::class, 'method' => 'getUserDeletedMap', 'params' => self::PARAMS_FORMAT], // 用户状态
        'user_duration'           => ['class' => StatusService::class, 'method' => 'getUserDurationMap', 'params' => self::PARAMS_FORMAT], // 用户封禁时间
        'user_macneed'            => ['class' => StatusService::class, 'method' => 'getUserMacneedMap', 'params' => self::PARAMS_FORMAT], // 封禁设备
        'user_macneedphone'       => ['class' => StatusService::class, 'method' => 'getUserMacneedPhoneMap', 'params' => self::PARAMS_FORMAT], // 同步安全手机号
        'user_forbid_reason'      => ['class' => StatusService::class, 'method' => 'getUserForbidReasonMap', 'params' => self::PARAMS_FORMAT], // 用户封禁原因
        'user_punish_ac'          => ['class' => StatusService::class, 'method' => 'getUserPunishAcMap', 'params' => self::PARAMS_FORMAT], // 用户罚款-操作类型
        'user_punish_reason'      => ['class' => StatusService::class, 'method' => 'getUserPunishReasonMap', 'params' => self::PARAMS_FORMAT], // 用户罚款-选择原因
        'user_punish_frozen_type' => ['class' => StatusService::class, 'method' => 'getUserPunishFrozenTypeMap', 'params' => self::PARAMS_FORMAT], // 用户罚款-类型
        'user_punish_super'       => ['class' => StatusService::class, 'method' => 'getUserPunishSuperMap', 'params' => self::PARAMS_FORMAT], // 用户罚款-退款方式
        'user_punish_moneyto'     => ['class' => StatusService::class, 'method' => 'getUserPunishMoneytoMap', 'params' => self::PARAMS_FORMAT], // 用户罚款-退款到
        'user_level_type'         => ['class' => StatusService::class, 'method' => 'getUserLevelTypeMap', 'params' => self::PARAMS_FORMAT], // 用户等级-获取类型
        'user_online_status'      => ['class' => StatusService::class, 'method' => 'getUserOnlineStatusMap', 'params' => self::PARAMS_FORMAT], // 用户等级-在线状态
        'medal'                   => ['class' => StatusService::class, 'method' => 'getMedalMap', 'params' => self::PARAMS_FORMAT], // 勋章
        'commodity'               => ['class' => StatusService::class, 'method' => 'getCommodityMap', 'params' => self::PARAMS_FORMAT], // 物品
        'room_background'         => ['class' => StatusService::class, 'method' => 'getRoomBackgroundMap', 'params' => self::PARAMS_FORMAT], // 房间背景
        'ka_org_user'             => ['class' => KaStatusService::class, 'method' => 'getOrgUserAdminList', 'params' => self::PARAMS_FORMAT], // ka组织架构-选择用户
        'ka_org_list'             => ['class' => KaStatusService::class, 'method' => 'getOrgList', 'params' => self::PARAMS_FORMAT], // ka组织架构-选择部门
    ];

    private $classNewMap = [
        'big_area_list' => ['class' => StatusService::class, 'method' => 'getFamilyBigArea', 'params' => self::PARAMS_FORMAT],
        'language_cn'   => ['class' => StatusService::class, 'method' => 'getLanguageNameMap', 'params' => self::PARAMS_FORMAT],
        'language_en'   => ['class' => StatusService::class, 'method' => 'getLanguageMap', 'params' => self::PARAMS_FORMAT],
        'sex'           => ['class' => StatusService::class, 'method' => 'getUserSexMap', 'params' => self::PARAMS_FORMAT],
        'yes_and_no'    => ['class' => StatusService::class, 'method' => 'getYesAndNoMap', 'params' => self::PARAMS_FORMAT],
    ];

    private $methodNameMap = [
        'big_area_list' => '运营大区',
        'language_en'   => '语言-英文',
        'language_cn'   => '语言-中文',
        'sex'           => '性别',
        'yes_and_no'    => '是/否',
    ];

    public function getListAction()
    {
        $type = $this->request->getQuery('type', 'trim', '');
        $res = [];

        if (empty($type)) {
            return $this->outputSuccess($res);
        }

        if (false === stripos($type, ',')) {
            if (!isset($this->classMap[$type])) {
                return $this->outputSuccess($res);
            }

            $class = $this->classMap[$type]['class'];
            $method = $this->classMap[$type]['method'];
            $params = $this->classMap[$type]['params'];

            $refiection = new \ReflectionMethod($class, $method);

            if ($refiection->isStatic()) {
                $res[$type] = call_user_func_array([$class, $method], $params);
            } else {
                $res[$type] = call_user_func_array([new $class, $method], $params);
            }
        } else {
            $type = explode(',', $type);

            foreach ($type as $item) {
                if (!isset($this->classMap[$item])) {
                    continue;
                }

                $class = $this->classMap[$item]['class'];
                $method = $this->classMap[$item]['method'];
                $params = $this->classMap[$item]['params'];

                $refiection = new \ReflectionMethod($class, $method);

                if ($refiection->isStatic()) {
                    $res[$item] = call_user_func_array([$class, $method], $params);
                } else {
                    $res[$item] = call_user_func_array([new $class, $method], $params);
                }
            }
        }

        return $this->outputSuccess($res);
    }

    /**
     * 零代码平台获取枚举方法
     */
    public function indexAction()
    {
        $type = $this->request->getQuery('type', 'trim', '');
        $res = [];

        if (empty($type)) {
            $map = [];
            foreach($this->methodNameMap as $key => $value) {
                $map[] = [
                    'label' => $value . '（' . $key . '）',
                    'value' => $key
                ];
            }
            return $this->outputSuccess($map);
        }

        if (false === stripos($type, ',')) {
            if (!isset($this->classNewMap[$type])) {
                return $this->outputSuccess($res);
            }

            $class = $this->classNewMap[$type]['class'];
            $method = $this->classNewMap[$type]['method'];
            $params = $this->classNewMap[$type]['params'];

            $refiection = new \ReflectionMethod($class, $method);

            if ($refiection->isStatic()) {
                $res[$type] = call_user_func_array([$class, $method], $params);
            } else {
                $res[$type] = call_user_func_array([new $class, $method], $params);
            }
        } else {
            $type = explode(',', $type);

            foreach ($type as $item) {
                if (!isset($this->classNewMap[$item])) {
                    continue;
                }

                $class = $this->classNewMap[$item]['class'];
                $method = $this->classNewMap[$item]['method'];
                $params = $this->classNewMap[$item]['params'];

                $refiection = new \ReflectionMethod($class, $method);

                if ($refiection->isStatic()) {
                    $res[$item] = call_user_func_array([$class, $method], $params);
                } else {
                    $res[$item] = call_user_func_array([new $class, $method], $params);
                }
            }
        }

        return $this->outputSuccess($res);
    }
}
