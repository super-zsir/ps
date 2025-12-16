<?php

namespace Imee\Comp\Common\Export\Models\Xsst;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;

    const SCHEMA = 'xsstdb';
    const SCHEMA_READ = 'xsstdb';
}
