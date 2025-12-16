<?php

namespace Imee\Comp\Nocode\Models\Cms;

use Imee\Comp\Nocode\Service\Exception\FormException;

class NocodeSchemaConfig extends BaseModel
{
    public static $createTime = 'create_time';
    public static $updateTime = 'update_time';

    /**
     * 根据标识获取信息
     * @param string $ncid
     * @return array
     */
    public static function getInfoByNcid(string $ncid): array
    {
        return self::findOneByWhere([['ncid', '=', $ncid], ['system_id', '=', SYSTEM_ID]], '*');
    }

    /**
     * 保存配置
     * @param string $ncid
     * @param string $schemaJson
     * @return array
     */
    public static function saveConfig(string $ncid, string $schemaJson): array
    {
        $config = self::findOneByWhere([['ncid', '=', $ncid], ['system_id', '=', SYSTEM_ID]], '*');

        $data = [
            'ncid' => $ncid,
            'system_id' => SYSTEM_ID,
            'schema_json' => $schemaJson
        ];

        $id = $config['id'] ?? 0;

        if (empty($config)) {
            list($result, $id) = self::add($data);
        } else {
            list($result, $msg) = self::edit($id, $data);
        }

        if (!$result) {
            [$code, $msg] = FormException::SAVE_ERROR;
            throw new FormException(sprintf($msg, $msg), $code);
        }

        return [true, $id];
    }
}