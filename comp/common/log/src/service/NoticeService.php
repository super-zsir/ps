<?php

namespace Imee\Comp\Common\Log\Service;

use Imee\Comp\Common\Log\Models\Cms\CmsModules;
use Imee\Comp\Common\Log\Models\Cms\CmsUser;
use Imee\Comp\Common\Log\Models\Xsst\XsstNoticeConfig;
use Imee\Comp\Common\Log\Models\Xsst\XsstNoticeGroupConfig;
use Imee\Comp\Common\Log\Models\Xsst\XsstNoticeLog;
use Imee\Comp\Common\Sdk\SdkSlack;
use Imee\Exception\ApiException;
use Imee\Service\Helper;

/**
 * 通知配置
 */
class NoticeService
{
    /**
     * 通知群列表
     * @param array $params
     * @return array
     */
    public static function getGroupList(array $params): array
    {
        $conditions = [
            ['status', '=', XsstNoticeGroupConfig::STATUS_VALID]
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['name', 'like', $params['name']];
        }
        $list = XsstNoticeGroupConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list)) {
            return $list;
        }

        $adminList = CmsUser::getUserNameList(array_column($list['data'], 'admin'));
        $useCountList = XsstNoticeConfig::getGroupUseCount(array_column($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $useCount = $useCountList[$item['id']] ?? 0;
            $item['use_count'] = [
                'title'  => $useCount,
                'value'  => $useCount,
                'type'   => 'guid',
                'guid'   => 'noticeconfig',
                'params' => [
                    'gid' => $item['id']
                ],
            ];
            $item['admin'] = $adminList[$item['admin']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    /**
     * 新增通知群
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function groupCreate(array $params): array
    {
        $name = trim($params['name'] ?? '');
        $webhook = trim($params['webhook'] ?? '');

        if (empty($name) || empty($webhook)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $data = [
            'name'     => $name,
            'webhook'  => $webhook,
            'admin'    => $params['admin_uid'],
            'dateline' => time(),
            'status'   => XsstNoticeGroupConfig::STATUS_VALID
        ];

        list($res, $msg) = XsstNoticeGroupConfig::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config create failed, message：' . $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    /**
     * 编辑通知群
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function groupModify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $name = trim($params['name'] ?? '');
        $webhook = trim($params['webhook'] ?? '');

        if (empty($id) || empty($name) || empty($webhook)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $info = XsstNoticeGroupConfig::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config not found');
        }

        $data = [
            'name'     => $name,
            'webhook'  => $webhook,
            'admin'    => $params['admin_uid'],
            'dateline' => time(),
            'status'   => XsstNoticeGroupConfig::STATUS_VALID
        ];

        list($res, $msg) = XsstNoticeGroupConfig::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config modify failed, message：' . $msg);
        }

        return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
    }

    /**
     * 删除通知群
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function groupDelete(array $params): array
    {
        $id = intval($params['id'] ?? 0);

        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $info = XsstNoticeGroupConfig::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config not found');
        }

        $data = [
            'status'   => XsstNoticeGroupConfig::STATUS_INVALID,
            'admin'    => $params['admin_uid'],
            'dateline' => time()
        ];

        list($res, $msg) = XsstNoticeGroupConfig::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config delete failed, message：' . $msg);
        }

        return ['id' => $id, 'before_json' => $info, 'after_json' => $data];
    }

    /**
     * 通知列表
     * @param array $params
     * @return array
     */
    public static function getNoticeList(array $params): array
    {
        $conditions = [
            ['status', '=', XsstNoticeConfig::STATUS_VALID]
        ];

        if (isset($params['name']) && !empty($params['name'])) {
            $conditions[] = ['name', 'like', $params['name']];
        }
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['gid', '=', $params['id']];
        }
        $list = XsstNoticeConfig::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list)) {
            return $list;
        }
        $adminList = CmsUser::getUserNameList(Helper::arrayFilter($list['data'], 'admin'));
        $moduleList = CmsModules::getModuleNameByModuleIds(Helper::arrayFilter($list['data'], 'mid'));
        $groupList = XsstNoticeGroupConfig::getNameByIds(Helper::arrayFilter($list['data'], 'gid'));
        $noticeCountList = XsstNoticeLog::getNoticeCount(array_column($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $noticeCount = $noticeCountList[$item['id']] ?? 0;
            $item['notice_count'] = [
                'title'  => $noticeCount,
                'value'  => $noticeCount,
                'type'   => 'guid',
                'guid'   => 'noticelog',
                'params' => [
                    'nid' => $item['id']
                ],
            ];
            $item['action_name'] = self::getActionName($item['mid'], $item['action']);
            $item['group_name'] = $groupList[$item['gid']] ?? '';
            $item['module_name'] = $moduleList[$item['mid']] ?? '';
            $item['admin'] = $adminList[$item['admin']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    /**
     * 通知配置创建
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function noticeCreate(array $params): array
    {
        $name = trim($params['name'] ?? '');
        $gid = intval($params['gid'] ?? 0);
        $action = $params['action'] ?? [];
        $mid = intval($params['mid'] ?? 0);
        if (empty($name) || empty($gid) || empty($action) || empty($mid)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $noticeGroup = XsstNoticeGroupConfig::findOne($gid);
        if (empty($noticeGroup)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config not found');
        }
        $module = CmsModules::findOne($mid);
        if (empty($module)) {
            throw new ApiException(ApiException::MSG_ERROR, 'module not found');
        }

        $data = [
            'name'     => $name,
            'gid'      => $gid,
            'mid'      => $mid,
            'action'   => implode(',', $action),
            'admin'    => $params['admin_uid'],
            'dateline' => time(),
            'status'   => XsstNoticeConfig::STATUS_VALID
        ];

        list($res, $msg) = XsstNoticeConfig::add($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'notice create failed, message：' . $msg);
        }

        return ['id' => $msg, 'after_json' => $data];
    }

    /**
     * 通知配置编辑
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function noticeModify(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        $name = trim($params['name'] ?? '');
        $gid = intval($params['gid'] ?? 0);
        $action = $params['action'] ?? [];
        $mid = trim($params['mid'] ?? '');

        // mid特殊处理一下
        if (!is_numeric($mid) && preg_match('/ID:(\d+)/', $mid, $matches)) {
            $extractedId = $matches[1];
            $mid = $extractedId;
        }

        if (empty($id) || empty($name) || empty($gid) || empty($action) || empty($mid)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $notice = XsstNoticeConfig::findOne($id);
        if (empty($notice)) {
            throw new ApiException(ApiException::MSG_ERROR, 'notice config not found');
        }

        $noticeGroup = XsstNoticeConfig::findOne($gid);
        if (empty($noticeGroup)) {
            throw new ApiException(ApiException::MSG_ERROR, 'group config not found');
        }
        $module = CmsModules::findOne($mid);
        if (empty($module)) {
            throw new ApiException(ApiException::MSG_ERROR, 'module not found');
        }

        $data = [
            'name'     => $name,
            'gid'      => $gid,
            'mid'      => (int)$mid,
            'action'   => implode(',', $action),
            'admin'    => $params['admin_uid'],
            'dateline' => time(),
            'status'   => XsstNoticeConfig::STATUS_VALID
        ];

        list($res, $msg) = XsstNoticeConfig::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'notice modify failed, message：' . $msg);
        }

        return ['id' => $msg, 'before_json' => $notice, 'after_json' => $data];
    }

    /**
     * 通知配置删除
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public static function noticeDelete(array $params): array
    {
        $id = intval($params['id'] ?? 0);
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'Required fields are not filled in');
        }

        $info = XsstNoticeConfig::findOne($id);

        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, 'notice config not found');
        }

        $data = [
            'status'   => XsstNoticeConfig::STATUS_INVALID,
            'admin'    => $params['admin_uid'],
            'dateline' => time(),
        ];

        list($res, $msg) = XsstNoticeConfig::edit($id, $data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, 'notice delete failed, message：' . $msg);
        }

        return ['id' => $msg, 'before_json' => $info, 'after_json' => []];
    }

    /**
     * 通知配置详情
     * @param int $id
     * @return array
     */
    public static function noticeDetail(int $id): array
    {
        if (empty($id)) {
            return [];
        }

        $info = XsstNoticeConfig::findOne($id);

        if ($info) {
            $module = CmsModules::findOne($info['mid']);
            $info['action_options'] = self::getActionMap($info['mid']);
            $info['mid'] = "【ID:{$module['module_id']}】{$module['module_name']}";
            $info['action'] = explode(',', $info['action']);
        }

        return $info;
    }

    /**
     * 通知日志列表
     * @param array $params
     * @return array
     */
    public static function getLogList(array $params): array
    {
        $conditions = [];
        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['nid', '=', $params['id']];
        }

        $list = XsstNoticeLog::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        $adminList = CmsUser::getUserNameList(Helper::arrayFilter($list['data'], 'admin'));

        foreach ($list['data'] as &$item) {
            [$controller, $action] = explode('.', $item['path']);
            $moduleName = CmsModules::getModuleNameByControllerAndAction($controller, $action);
            $item['path_info'] = "功能：{$moduleName}" . '<br />' . "path: {$item['path']}";
            $item['admin'] = $adminList[$item['admin']] ?? '';
            $item['dateline'] = Helper::now($item['dateline']);
        }

        return $list;
    }

    /**
     * 记录访问需要通知的记录
     * @param array $params
     * @return bool
     */
    public static function addNoticeLog(array $params): bool
    {
        $url = self::formatUrl($params['_url'] ?? '');
        if (empty($url)) {
            return false;
        }
        $actionMap = XsstNoticeConfig::getActionMap();
        $nidList = $actionMap[$url] ?? [];
        if (empty($nidList)) {
            return false;
        }

        $logBaseData = [
            'params'   => json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'path'     => $url,
            'admin'    => $params['operate_id'],
            'dateline' => time(),
            'status'   => XsstNoticeLog::STATUS_WAIT
        ];

        $logData = [];

        foreach ($nidList as $nid) {
            $logData[] = array_merge($logBaseData, ['nid' => $nid]);
        }
        XsstNoticeLog::addBatch($logData);
        return true;
    }

    /**
     * 敏感权限通知操作通知
     * @return void
     * @throws \Exception
     */
    public static function sendNotice()
    {
        $logList = XsstNoticeLog::getSendNoticeList();

        /** @var SdkSlack $slack */
        $slack = factory_single_obj(SdkSlack::class);

        $content = <<<STR
敏感权限操作通知
> 操作菜单: {module_name}
> 操作内容: {params}
> 操作人: {admin}
> 操作时间:{dateline}
STR;
        $adminList = CmsUser::getUserNameList(array_column($logList, 'admin'));
        $updateData = [
            'status' => XsstNoticeLog::STATUS_SENDING,
            'dateline' => time()
        ];
        $updateBatch = [];
        foreach ($logList as $log) {
            [$controller, $action] = explode('.', $log['path']);
            $moduleName = CmsModules::getModuleNameByControllerAndAction($controller, $action);
            $admin = $adminList[$log['admin']] ?? $log['admin'];
            $slackMsg = str_replace(
                ['{module_name}', '{params}', '{admin}', '{dateline}'],
                [$moduleName, $log['params'], $admin, date('Y-m-d H:i:s')],
                $content
            );
            $slack->sendMsg($log['webhook'], 'markdown', $slackMsg);
            usleep(10 * 1000);
            $updateBatch[$log['id']] = $updateData;
        }
        // 更新发送状态
        XsstNoticeLog::updateBatch($updateBatch);
    }

    /**
     * 转换url格式
     * @param string $url
     * @return string
     */
    public static function formatUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $trimmedPath = preg_replace('#^/api/#', '', $url);
        // 用 '.' 替换最后一个 '/'
        $parts = explode('/', $trimmedPath);
        $lastPart = array_pop($parts);

        return implode('/', $parts) . '.' . $lastPart;
    }

    /**
     * 获取操作名称
     * @param int $pid
     * @param string $action
     * @return string
     */
    private static function getActionName(int $pid, string $action): string
    {
        $nameMap = CmsModules::getModuleNameByPid($pid);
        $nameArray = [];
        $actionArray = explode(',', $action);
        if (empty($actionArray)) {
            return '';
        }

        foreach ($actionArray as $action) {
            $name = $nameMap[$action] ?? '';
            $name && $nameArray[] = $name;
        }

        return implode("<br />", $nameArray);
    }

    /**
     * 获取操作map
     * @return array
     */
    public static function getActionMap(int $mid): array
    {
        $childrenModules = CmsModules::getListByWhere([
            ['parent_module_id', '=', $mid],
            ['m_type', '=', CmsModules::M_TYPE_PAGE],
            ['is_action', '=', CmsModules::IS_ACTION_YES],
            ['deleted', '=', CmsModules::DELETED_NO],
            ['system_id', '=', SYSTEM_ID],
        ], 'module_id, module_name, controller, action');

        $data = [];
        foreach ($childrenModules as $childrenModule) {
            $data[] = [
                'label' => "【ID：{$childrenModule['module_id']}】{$childrenModule['module_name']}",
                'value' => $childrenModule['controller'] . '.' . $childrenModule['action']
            ];
        }

        return $data;
    }

    /**
     * 获取模块map
     * @param $name
     * @return array
     */
    public static function getModuleMap($name): array
    {
        if (empty($name)) {
            return [];
        }
        // 只获取最底层的菜单权限
        $moduleList = CmsModules::getListByWhere([
            ['module_name', 'like', $name],
            ['m_type', '=', CmsModules::M_TYPE_PAGE],
            ['is_action', '=', CmsModules::IS_ACTION_NO],
            ['deleted', '=', CmsModules::DELETED_NO],
            ['system_id', '=', SYSTEM_ID],
            ['root_path', '=', '']
        ], 'module_id, module_name');

        $data = [];
        foreach ($moduleList as $module) {
            $data[] = [
                'label' => "【ID:{$module['module_id']}】{$module['module_name']}",
                'value' => $module['module_id']
            ];
        }

        return $data;
    }

    /**
     * 获取options
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            'group' => self::getNoticeGroupMap()
        ];
    }

    /**
     * 获取通知群map
     * @return array
     */
    public static function getNoticeGroupMap(): array
    {
        $list = XsstNoticeGroupConfig::getListByWhere([['status', '=', XsstNoticeGroupConfig::STATUS_VALID]], 'id, name', 'id desc');
        if (empty($list)) {
            return $list;
        }

        $map = [];
        foreach ($list as $item) {
            $map[] = [
                'label' => "【ID:{$item['id']}】{$item['name']}",
                'value' => $item['id']
            ];
        }

        return $map;
    }
}