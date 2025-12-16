<?php

namespace Imee\Models\Xsst;

use Imee\Comp\Common\Orm\Traits\MysqlCollectionTrait;

class XsstUidWhiteList extends BaseModel
{

    use MysqlCollectionTrait;

    const WHITE_LIST_SYS_FORBIDDEN = 16;

}