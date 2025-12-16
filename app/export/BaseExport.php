<?php

namespace Imee\Export;

use Imee\Helper\Traits\ExportCsvTrait;

abstract class BaseExport
{
    use ExportCsvTrait;

    abstract public function export($filePathName, $filterParams);

    abstract public function getTitle();

    /**
     * @desc 设置cvs文件的标题行
     * @param $tmpLockFile
     */
    protected function setFileHeader($tmpLockFile)
    {
        file_put_contents($tmpLockFile, pack('H*', 'EFBBBF'), FILE_APPEND);
        file_put_contents($tmpLockFile, implode(',', $this->getTitle()) . "\n", FILE_APPEND);
    }
}
