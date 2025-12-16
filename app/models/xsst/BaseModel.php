<?php


namespace Imee\Models\Xsst;

use Imee\Comp\Common\Orm\Traits\ModelManagerTrait;
use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{

    use MysqlCollectionTrait;
    use ModelManagerTrait;

    const STATUS_NORMAL = 1;
    const STATUS_DELETE = 2;

    public const SCHEMA = 'xsstdb';
    public const SCHEMA_READ = 'xsstdb';
    
    protected $isReadWriteSeparation = false;

}
