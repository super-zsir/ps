<?php


namespace Imee\Models\Config;

class XsstDeviceWhitelist extends BaseModel
{
    public static $primaryKey = 'id';

    const DEVICE_TYPE_DID = 1;
    const DEVICE_TYPE_MAC = 2;

    const DELETED_NO = 0;
    const DELETED_YES = 1;

    public static $deviceType = [
        self::DEVICE_TYPE_DID => 'DID',
        self::DEVICE_TYPE_MAC => 'MAC',
    ];
}