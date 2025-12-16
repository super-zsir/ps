<?php

namespace Imee\Comp\Common\Export\Models\Xsst;

class XsstExportTask extends BaseModel
{
    public static $primaryKey = 'id';

    const FILE_TYPE_MAP = [
        'csv', 'xlsx', 'ods'
    ];

    const STATUS_PENDING = 0;
    const STATUS_EXECUTING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILED = 3;
    const STATUS_MAP = [
        self::STATUS_PENDING   => '待导出',
        self::STATUS_EXECUTING => '导出中',
        self::STATUS_SUCCESS   => '导出成功',
        self::STATUS_FAILED    => '导出失败',
    ];

    public function beforeCreate()
    {
        $now = time();
        $this->created_at = $now;
        $this->updated_at = $now;
    }

    public function beforeUpdate()
    {
        $this->updated_at = time();
    }

    /**
     * @return mixed
     */
    public function getExportParams()
    {
        return unserialize($this->export_params);
    }

    /**
     * @param mixed $exportParams
     */
    public function setExportParams($exportParams): void
    {
        $this->export_params = serialize($exportParams);
    }
}
