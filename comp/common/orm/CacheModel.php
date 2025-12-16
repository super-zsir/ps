<?php

namespace Imee\Comp\Common\Orm;

use Phalcon\Mvc\Model;
use Phalcon\Di;
use Phalcon\Cache\Exception;

class CacheModel extends Model
{
    protected $disabledSetter = true;

    private static $lastInsertId = [];

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function __set($property, $value)
    {
        if ($this->disabledSetter) {
            $this->{$property} = $value;
            return $value;
        } else {
            return parent::__set($property, $value);
        }
    }

    public static function logger()
    {
        return Di::getDefault()->getShared('logger');
    }

    public static function logException($e)
    {
        self::logger()->error($e->getFile() . $e->getLine() . $e->getMessage());
    }

    public function save($data = null, $whiteList = null)
    {
        $success = parent::save($data, $whiteList);
        if (!$success) {
            $msg = '';
            foreach ($this->getMessages() as $message) {
                $msg .= $message->getMessage() . ", ";
            }
            $table = $this->getSource();
            throw new Exception("[{$table}]" . $msg);
        }
        return $success;
    }

    public static function saveModel($data)
    {
        if (empty($data)) {
            return false;
        }

        $rec = new static();
        foreach ($data as $k => $v) {
            $rec->{$k} = $v;
        }

        $result = $rec->save();
        self::$lastInsertId[static::class] = $rec->getWriteConnection()->lastInsertId($rec->getSource());
        return $result;
    }

    public static function useDb($db = '')
    {
        $rec = new static();
        if ($db == "") {
            return $rec;
        }

        $rec->setConnectionService($db);
        return $rec;
    }

    /**
     * @return int
     */
    public static function lastInsertId(): int
    {
        return self::$lastInsertId[static::class] ?? 0;
    }

    public static function modelsManager()
    {
        return Di::getDefault()->getShared('modelsManager');
    }

    public static function getTableName(): string
    {
        return (new static())->getSource();
    }

    public static function getTableFields(): array
    {
        return array_keys((new static())->toArray());
    }
}
