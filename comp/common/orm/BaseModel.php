<?php

namespace Imee\Comp\Common\Orm;

class BaseModel extends CacheModel
{

    // 可读写
    const SCHEMA = '';
    // 只读
    const SCHEMA_READ = '';

    // 开启读写分离
    protected $isReadWriteSeparation = true;
    // 允许字符串为空的字段
    protected $allowEmptyStringArr = [];

    // 初始化
    public function initialize()
    {
        parent::initialize();

        if (true === $this->isReadWriteSeparation) {
            $this->setWriteConnectionService(static::SCHEMA);
            $this->setReadConnectionService(static::SCHEMA_READ);
        } else {
            $this->setConnectionService(static::SCHEMA);
        }

        $this->allowEmptyStringValues($this->allowEmptyStringArr);
    }

    // 强制使用主库
    public static function useMaster()
    {
        $ns = new static();
        $ns->setConnectionService(static::SCHEMA);
        return $ns;
    }
}