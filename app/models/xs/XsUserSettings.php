<?php


namespace Imee\Models\Xs;

class XsUserSettings extends BaseModel
{
    protected static $primaryKey = 'uid';

    public static function getLanguage($uid)
    {
        $userSettings = XsUserSettings::findFirst(array(
            'columns'    => 'language',
            'conditions' => "uid = :uid:",
            'bind'       => array('uid' => $uid)
        ));
        return $userSettings ? $userSettings->language : 'zh_tw';
    }

    public static function getUserLanguageDb($uid)
    {
        $userSettings = XsUserSettings::findOneByWhere([
            ['uid', '=', $uid]
        ]);
        return $userSettings ? $userSettings['language'] : 'zh_tw';
    }

    public static function getUserSettingBatch($uidArr = [], $fieldArr = ['uid', 'language'])
    {
        if (empty($uidArr)) {
            return [];
        }
        $colums = implode(',', $fieldArr);
        $data = self::getListByWhere([
            ['uid', 'in', $uidArr]
        ], $colums);
        return array_column($data, null, 'uid');
    }
}
