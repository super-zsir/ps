<?php

namespace Imee\Models\Shared;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;

    const SCHEMA = 'bms_shared';
    const SCHEMA_READ = 'bms_shared';
}
