<?php

namespace Imee\Models\Union;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{

    use MysqlCollectionTrait;


    const STATUS_NORMAL = 1;
    const STATUS_DELETE = 2;

    public const SCHEMA = 'uniondb';
    public const SCHEMA_READ = 'unionslavedb';

}