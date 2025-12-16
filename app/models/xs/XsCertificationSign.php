<?php

namespace Imee\Models\Xs;

class XsCertificationSign extends BaseModel
{
    protected static $primaryKey = 'id';

    /**
     * 获取认证图标列表默认文案
     *
     * @return array
     */
    public static function getContentMap(): array
    {
        $data = self::getListByWhere([], 'id, default_content', 'id desc');

        $map = [];

        foreach ($data as $item) {
            $map[$item['id']] = $item['default_content'];
        }

        return $map;
    }

    public static function getOptions(): array
    {
        $data = self::getListByWhere([], 'id, name', 'id desc');

        $map = [];

        foreach ($data as $item) {
            $map[$item['id']] = $item['id'] . '-' . $item['name'];
        }

        return $map;
    }

    public static function getInfo(int $id): array
    {
        return self::findOne($id);
    }
}