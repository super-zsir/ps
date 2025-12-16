<?php

namespace Imee\Models\Config;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;

    const SCHEMA = 'bbcdb';
    const SCHEMA_READ = 'bbcslavedb';
}
