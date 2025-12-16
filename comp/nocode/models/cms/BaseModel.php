<?php


namespace Imee\Comp\Nocode\Models\Cms;

use Imee\Comp\Common\Orm\BaseModel as BModel;
use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class BaseModel extends BModel
{
    use MysqlCollectionTrait;

    const SCHEMA = 'cms';
    const SCHEMA_READ = 'cms';
}