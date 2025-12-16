<?php

namespace Imee\Comp\Common\Export\Service;

use Dcat\EasyExcel\Excel;
use Imee\Comp\Common\Export\Models\Redis\ExportTaskRedis;
use Imee\Comp\Common\Export\Models\Xsst\XsstExportTask;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Exception\ApiException;

class ExportService
{
    public static function showHtml()
    {
        $pre = LOGIN_JUMP_URL;
        if (!$pre) {
            $pre = '/';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>export</title>
    <script>
        setTimeout(function() {
            window.location.href = '{pre}common/export/export/list';
        }, 5000);
    </script>
</head>
<body>
    <p id="loading_id">SUCCESS. Automatically redirect to export list after 5 seconds.</p>
    <a href="{pre}common/export/export/list">If the page does not automatically redirect, please click here</a>
</body>
</html>
HTML;
        echo str_replace('{pre}', $pre, $html);
        exit;
    }

    public static function addTask($opUid, $fileName, callable $callback, $filterParams = [], $title = '')
    {
        $opUid = (int)$opUid;
        if ($opUid < 1) {
            throw new ApiException(ApiException::MSG_ERROR, '操作用户uid错误/必须登录');
        }
        if (empty($fileName)) {
            throw new ApiException(ApiException::MSG_ERROR, '导出文件名不能为空');
        }
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!in_array($fileType, XsstExportTask::FILE_TYPE_MAP)) {
            throw new ApiException(ApiException::MSG_ERROR, '不支持的导出格式');
        }

        if (!ExportTaskRedis::checkFirstExportFlag($opUid, $fileName, $filterParams)) {
            throw new ApiException(ApiException::MSG_ERROR, '最近已经导出过请到导出列表查看');
        }

        $model = new XsstExportTask();
        $model->title = $title;
        $model->op_uid = $opUid;
        $model->file_name = $fileName;
        $model->file_type = $fileType;
        $model->status = XsstExportTask::STATUS_PENDING;
        $model->project = SYSTEM_FLAG;
        $model->setExportParams([
            'filter_params' => $filterParams,
            'callback'      => $callback,
        ]);
        if (!$model->save()) {
            ExportTaskRedis::deleteFirstExportFlag($opUid, $fileName, $filterParams);
            throw new ApiException(ApiException::MSG_ERROR, '添加导出任务失败');
        }
    }

    public static function getList(array $where, $limit = 20, $page = 1): array
    {
        $page < 1 && $page = 1;
        $conditions = [];
        $conditions[] = ['project', '=', SYSTEM_FLAG];
        if (!empty($where['op_uid'])) {
            $conditions[] = ['op_uid', '=', $where['op_uid']];
        }
        $cols = 'id,op_uid,title,file_name,file_url,file_type,project,remark,status,created_at,updated_at,completion_at';
        $result = XsstExportTask::getListAndTotal($conditions, $cols, 'id desc', $page, $limit);
        if (!$result['data']) {
            return $result;
        }
        $uidArr = array_column($result['data'], 'op_uid');
        $uidArr = array_values(array_filter(array_unique($uidArr)));
        $userList = CmsUser::getAdminUserBatch($uidArr);
        $nowTime = time();
        foreach ($result['data'] as &$li) {
            $timeOut = $li['completion_at'] + (3 * 86400);
            $li['file_url'] = $nowTime > $timeOut ? '' : $li['file_url'];
            $li['op_uid'] = $li['op_uid'] . '-' . ($userList[$li['op_uid']]['user_name'] ?? '');
            $li['status_txt'] = XsstExportTask::STATUS_MAP[$li['status']] ?? '';
            $li['created_at'] = date('Y-m-d H:i:s', $li['created_at']);
            $li['updated_at'] = date('Y-m-d H:i:s', $li['updated_at']);
            $li['completion_at'] = $li['completion_at'] ? date('Y-m-d H:i:s', $li['completion_at']) : '';
        }

        return $result;
    }

    public static function getTask($id = 0)
    {
        return XsstExportTask::findFirst([
            'id > :id: AND status = :status: AND project = :project:',
            'bind' => [
                'id'      => (int)$id,
                'status'  => XsstExportTask::STATUS_PENDING,
                'project' => SYSTEM_FLAG,
            ]
        ]);
    }

    public static function getTaskById($id)
    {
        return XsstExportTask::findFirst([
            'id = :id:',
            'bind' => [
                'id' => (int)$id,
            ]
        ]);
    }

    public static function export($filePathName, $filterParams): bool
    {
        $headings = [
            'id'            => '任务编号',
            'op_uid'        => '操作用户',
            'title'         => '标题',
            'file_name'     => '文件名',
            'file_type'     => '格式',
            'status_txt'    => '状态',
            'created_at'    => '创建时间',
            'completion_at' => '完成时间',
            'remark'        => '备注',
        ];

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = self::getList($filterParams, $pageSize, $page);
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }
}
