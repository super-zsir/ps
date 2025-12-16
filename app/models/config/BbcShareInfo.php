<?php
/**
 * 分享信息文案表
 */

namespace Imee\Models\Config;

class BbcShareInfo extends BaseModel
{
    const DELETED_YES = 1;
    const DELETED_NO = 0;

    public static function insert($params): bool
    {
        $model = new self();
        $model->language = $params['language'];
        $model->type = $params['type'];
        $model->content = $params['content'];
        $model->deleted = $params['deleted'];
        $model->dateline = time();
        return $model->save();
    }

    public static function modify($id, $params): bool
    {
        $rec = self::findFirst([
            "conditions" => "id = :id:",
            "bind"       => ["id" => $id],
            "master"     => 1
        ]);

        if (!$rec) {
            return false;
        }

        if (isset($params['language'])) {
            $rec->language = $params['language'];
        }
        if (isset($params['type'])) {
            $rec->type = $params['type'];
        }
        if (isset($params['content'])) {
            $rec->content = $params['content'];
        }
        if (isset($params['deleted'])) {
            $rec->deleted = $params['deleted'];
        }

        $rec->dateline = time();
        return $rec->save();
    }

    public static function getTypes(): array
    {
        return [
            1 => '语音房分享页面文本',
            2 => 'unity房分享页面文本',
            3 => '房主分享H5',
            4 => '房客分享H5',
            5 => 'unity分享H5标题',
            6 => 'unity分享H5内容',
        ];
    }
}