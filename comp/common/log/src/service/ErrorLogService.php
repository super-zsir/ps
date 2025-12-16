<?php

namespace Imee\Comp\Common\Log\Service;

use Imee\Comp\Common\Log\Models\Xsst\BmsErrorLog;
use Imee\Comp\Common\Log\Service\Constant\LogConstant;
use Imee\Comp\Common\Sdk\SdkSlack;
use Imee\Comp\Operate\Auth\Service\Context\Modules\GetInfoContext;
use Imee\Comp\Operate\Auth\Service\Context\Modules\InfoContext;
use Imee\Comp\Operate\Auth\Service\ModulesService;
use Imee\Service\Helper;
use Phalcon\Di;

/**
 * 巡检日志服务
 */
class ErrorLogService
{
    public static $sensitiveFields = ['password', 'repassword'];

    public static function getList(array $params): array
    {
        $conditions = [];

        if (!empty($params['dateline_sdate'])) {
            $conditions[] = ['dateline', '>=', strtotime($params['dateline_sdate'])];
        }
        if (!empty($params['dateline_edate'])) {
            $conditions[] = ['dateline', '<=', strtotime($params['dateline_edate'])];
        }

        $list = BmsErrorLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['dateline'] = Helper::now($item['dateline']);
        }
        return $list;
    }

    public static function addLog(array $params): array
    {
        $di = Di::getDefault();
        $controller = $di->getShared('dispatcher')->getControllerName();
        $action = $di->getShared('dispatcher')->getActionName();
        $requestParams = array_merge($di->getRequest()->getQuery(), $di->getRequest()->getPost());

        // 过滤敏感字段
        $requestParams = self::filterSensitiveFields($requestParams, self::$sensitiveFields);

        //兼容程序报错后获取不到controller 和 action
        if (!$controller || !$action) {
            [$moduleName, $actionName] = ['', ''];
            dd($params['message']);
        } else {
            [$moduleName, $actionName] = self::getModuleName($controller, $action);
        }
        $data = [
            'admin_id'      => $params['admin_id'] ?? Helper::getSystemUid(),
            'module_name'   => $moduleName,
            'path'          => $controller . '/' . $action,
            'action'        => $actionName,
            'request_param' => json_encode($requestParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'message'       => $params['message'],
            'admin_name'    => $params['admin_name'] ?? (Helper::getSystemUserInfo()['user_name'] ?? ''),
            'status'        => BmsErrorLog::STATUS_WAIT,
        ];

        return BmsErrorLog::add($data);
    }

    private static function filterSensitiveFields($params, $sensitiveFields)
    {
        foreach ($sensitiveFields as $field) {
            if (isset($params[$field])) {
                unset($params[$field]);
            }
        }
        return $params;
    }

    private static function getModuleName(string $controller, string $action): array
    {
        $service = new ModulesService();

        // 当前访问模块
        $module = $service->getInfoByGuidAndAction(new GetInfoContext([
            'controller' => $controller,
            'action'     => $action
        ]));

        // 默认给控制器+方法名作为模块名称
        $moduleName = $controller . '/' . $action;
        if ($module) {
            $action = $module->module_name;
            // 父级权限点
            $parentModule = $service->getInfoById(new InfoContext([
                'module_id' => $module->parent_module_id,
            ]));

            $parentModule && $moduleName = $parentModule['module_name'];
        }

        return [$moduleName, $action];
    }

    /**
     * 异常信息通知
     * @return void
     * @throws \Exception
     */
    public static function errorNotice()
    {
        // todo 错误记录不会太多，直接取待发全部吧
        $logList = BmsErrorLog::getListByWhere([
            ['status', '=', BmsErrorLog::STATUS_WAIT]
        ]);

        /** @var SdkSlack $slack */
        $slack = factory_single_obj(SdkSlack::class);

        $content = <<<STR
> 巡检日志错误告警!!!
> 操作模块: {module_name}
> 请求参数: {params}
> 异常信息: {error_message}
> 操作人: {admin}
> 异常时间: {dateline}
STR;
        $updateData = [
            'status' => BmsErrorLog::STATUS_SENDING,
        ];
        $updateBatch = [];
        foreach ($logList as $log) {
            $slackMsg = str_replace(
                ['{module_name}', '{params}', '{error_message}', '{admin}', '{dateline}', '{handler}'],
                [$log['module_name'] . $log['action'], $log['request_param'], $log['message'], $log['admin_name'], Helper::now($log['dateline'])],
                $content
            );
            $slack->sendMsg(LogConstant::WARNING_SLACK_WEBHOOK, 'markdown', $slackMsg);
            usleep(10 * 1000);
            $updateBatch[$log['id']] = $updateData;
        }
        // 更新发送状态
        BmsErrorLog::updateBatch($updateBatch);
    }
}
