<?php

namespace Imee\Models\Lemon;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\ModelManagerTrait;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;
    use ModelManagerTrait;

    public const SCHEMA = 'lemondb';
    public const SCHEMA_READ = 'lemon_slavedb';
}
