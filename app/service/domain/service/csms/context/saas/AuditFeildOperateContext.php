<?php

namespace Imee\Service\Domain\Service\Csms\Context\Saas;

use Imee\Service\Domain\Context\BaseContext;

class AuditFeildOperateContext extends BaseContext
{
    public $id;

    public $cid;

    public $field;

    public $type;

    public $dependField;

    public $sort;

    public $state;

    public $dbName;

    public $tableName;

    public $pkField;

    public $uidField;

    public $joinup;

    public $ignoreWrite;

    public $ignoreUpdate;

    public $admin;
}
