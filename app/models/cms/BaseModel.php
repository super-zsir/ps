<?php

namespace Imee\Models\Cms;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\ModelManagerTrait;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;
    use ModelManagerTrait;

    const SCHEMA = 'cms';
    const SCHEMA_READ = 'cms';

    protected $isReadWriteSeparation = false;
}
