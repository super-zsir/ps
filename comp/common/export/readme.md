# 导出任务组件
## cp docs/supervisor下配置到 docs/supervisor下，修改配置
## 启动守护进程

```PHP
    /**
     * @point 导出
     */
    public function exportAction()
    {
        ...
        //以文件名后缀识别导出指定类型文件，支持xlsx csv ods文件导出
        ExportService::addTask($this->uid, 'export.csv', [ExportService::class, 'export'], $params, '导出任务title');
        //为了兼容之前导出任务新窗口打开的
        //ExportService::showHtml();
        return $this->outputSuccess();
    }

    //ExportService
    public static function export($filePathName, $filterParams): bool
    {
        $headings = [
            'id'            => '任务编号',
            'op_uid'        => '操作用户',
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
```
