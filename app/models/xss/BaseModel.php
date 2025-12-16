<?php

namespace Imee\Models\Xss;

use Imee\Comp\Common\Orm\Traits\ModelManagerTrait;
use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    const STATUS_NORMAL = 1;
    const STATUS_DELETE = 2;

    use MysqlCollectionTrait;
    use ModelManagerTrait;

    public const SCHEMA = 'xssdb';
    public const SCHEMA_READ = 'xssdb';
    protected $isReadWriteSeparation = false;

    protected $allowEmptyStringArr = [];
}
